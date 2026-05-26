<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inmueble;
use App\Models\Parentesco;
use App\Models\Person;
use App\Models\TipoTransmision;
use App\Models\Tramite;
use App\Services\IdtgbCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TramiteWizardController extends Controller
{
    private function getSessionKey()
    {
        return 'tramite_wizard_data';
    }

    private function initializeWizard(Request $request)
    {
        if (! $request->session()->has($this->getSessionKey())) {
            $request->session()->put($this->getSessionKey(), [
                'step1' => [],
                'step2' => ['disponentes' => []],
                'step3' => ['adquirentes' => []],
                'step4' => ['inmuebles' => []],
                'step5' => ['documentos' => []], // <-- NUEVO: Paso de documentos
                'step6' => ['exenciones' => []], // Renumerado
                'current_tramite_id' => null,
            ]);
        }
    }

    private function getWizardData(Request $request)
    {
        return $request->session()->get($this->getSessionKey());
    }

    private function updateWizardData(Request $request, $data)
    {
        $request->session()->put($this->getSessionKey(), $data);
    }

    private function validateWizardIntegrity(array $wizardData)
    {
        $errors = [];

        if (! empty($wizardData['step2']['disponentes'])) {
            foreach ($wizardData['step2']['disponentes'] as $personId) {
                if (! is_numeric($personId) || Person::where('id', $personId)->doesntExist()) {
                    $errors[] = "Disponente inválido: ID {$personId}";
                }
            }
        }

        if (! empty($wizardData['step3']['adquirentes'])) {
            foreach ($wizardData['step3']['adquirentes'] as $adquirente) {
                $personId = $adquirente['person_id'] ?? null;
                if (! is_numeric($personId) || Person::where('id', $personId)->doesntExist()) {
                    $errors[] = "Adquirente inválido: ID {$personId}";
                }
            }
        }

        if (! empty($wizardData['step4']['inmuebles'])) {
            foreach ($wizardData['step4']['inmuebles'] as $inmuebleId) {
                if (! is_numeric($inmuebleId) || Inmueble::where('id', $inmuebleId)->doesntExist()) {
                    $errors[] = "Inmueble inválido: ID {$inmuebleId}";
                }
            }
        }

        if (! empty($errors)) {
            throw new \InvalidArgumentException('Datos del wizard corruptos: '.implode(', ', $errors));
        }

        return true;
    }

    // ==================== NUEVO MÉTODO: EDITAR ====================
    public function edit($id, Request $request)
    {
        $tramite = Tramite::with(['disponentes', 'adquirentes', 'inmuebles', 'documentos', 'tramiteExenciones'])->findOrFail($id);

        if (in_array($tramite->estado, ['Finalizado', 'Anulado', 'Pagado'])) {
            return redirect()->route('admin.tramites.index')
                ->with(['message' => 'No se puede editar un trámite en estado '.$tramite->estado, 'alert-type' => 'error']);
        }

        $this->initializeWizard($request);

        $wizardData = [
            'current_tramite_id' => $tramite->id,
            'step1' => [
                'nro_tramite' => $tramite->nro_tramite,
                'fecha_presentacion' => $tramite->fecha_presentacion->format('Y-m-d'),
                'fecha_transmision' => $tramite->fecha_transmision->format('Y-m-d'),
                'tipo_transmision_id' => $tramite->tipo_transmision_id,
                'valor_declarado' => $tramite->valor_declarado,
                'base_imponible' => $tramite->base_imponible,
                'observaciones' => $tramite->observaciones,
            ],
            'step2' => [
                'disponentes' => $tramite->disponentes->pluck('person_id')->toArray(),
            ],
            'step3' => [
                'adquirentes' => $tramite->adquirentes->map(function ($adq) {
                    return [
                        'person_id' => $adq->person_id,
                        'parentesco_id' => $adq->parentesco_id,
                        'porcentaje' => $adq->porcentaje,
                    ];
                })->toArray(),
            ],
            'step4' => [
                'inmuebles' => $tramite->inmuebles->pluck('id')->toArray(),
            ],
            'step5' => [
                'documentos' => $tramite->documentos->where('vigente', true)->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'tipo_doc' => $doc->tipo_doc,
                        'person_id' => $doc->person_id,
                        'temp_path' => null,
                        'original_name' => $doc->original_name ?? basename($doc->archivo_path),
                        'existing' => true,
                    ];
                })->toArray(),
            ],
            'step6' => [
                'exenciones' => $tramite->tramiteExenciones->pluck('exencion_id')->toArray(),
            ],
        ];

        $this->updateWizardData($request, $wizardData);

        return redirect()->route('admin.tramites.wizard.create.step1');
    }

    // ==================== PASO 1: DATOS GENERALES ====================
    public function createStep1(Request $request)
    {
        if (! $request->session()->has($this->getSessionKey())) {
            $this->initializeWizard($request);
        }
        $wizardData = $this->getWizardData($request);
        $tiposTransmision = TipoTransmision::all();

        $isEdit = ! empty($wizardData['current_tramite_id']);
        $actionText = $isEdit ? 'Editar' : 'Crear';

        return view('admin.tramites.wizard.create_step_1', [
            'tiposTransmision' => $tiposTransmision,
            'wizardData' => $wizardData,
            'step_title' => "Paso 1: Datos Generales ({$actionText})",
            'current_step' => 1,
            'total_steps' => 7,
            'progress' => 14,
            'is_edit' => $isEdit,
            'current_tramite_id' => $wizardData['current_tramite_id'] ?? null,
        ]);
    }

    public function postStep1(Request $request)
    {
        $wizardData = $this->getWizardData($request);
        $currentId = $wizardData['current_tramite_id'] ?? null;

        $validated = $request->validate([
            'nro_tramite' => [
                'required',
                'string',
                'max:15',
                Rule::unique('tramites', 'nro_tramite')->ignore($currentId),
            ],
            'fecha_presentacion' => 'required|date',
            'fecha_transmision' => 'required|date|before_or_equal:fecha_presentacion', // Validación lógica
            'tipo_transmision_id' => 'required|exists:tipos_transmision,id',
            'valor_declarado' => 'required|numeric|min:0',
            'base_imponible' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $wizardData['step1'] = $validated;
        $this->updateWizardData($request, $wizardData);

        return redirect()->route('admin.tramites.wizard.create.step2');
    }

    // ==================== PASO 2: DISPONENTES ====================
    public function createStep2(Request $request)
    {
        $wizardData = $this->getWizardData($request);
        if (empty($wizardData['step1'])) {
            return redirect()->route('admin.tramites.wizard.create.step1');
        }

        $disponentes = collect($wizardData['step2']['disponentes'])->map(function ($personId) {
            return Person::find($personId);
        })->filter();

        $isEdit = ! empty($wizardData['current_tramite_id']);

        return view('admin.tramites.wizard.create_step_2', [
            'disponentes' => $disponentes,
            'step_title' => 'Paso 2: Disponentes',
            'current_step' => 2, 'total_steps' => 7, 'progress' => 28,
            'is_edit' => $isEdit,
            'current_tramite_id' => $wizardData['current_tramite_id'] ?? null,
        ]);
    }

    public function addDisponente(Request $request)
    {
        $request->validate(['person_id' => 'required|exists:people,id']);

        $wizardData = $this->getWizardData($request);
        $personId = $request->person_id;

        // Verificar que no esté ya como adquirente
        if (in_array($personId, $wizardData['step3']['adquirentes'] ?? [])) {
            return back()->withErrors('Esta persona ya está agregada como adquirente.');
        }

        // Agregar a disponentes si no existe
        if (! in_array($personId, $wizardData['step2']['disponentes'])) {
            $wizardData['step2']['disponentes'][] = $personId;
            $this->updateWizardData($request, $wizardData);
        }
        // SINTONÍA: Si es AJAX, responde JSON. Si no, redirige.
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Disponente agregado']);
        }

        return redirect()->route('admin.tramites.wizard.create.step2');
    }

    public function removeDisponente(Request $request, $personId)
    {
        $wizardData = $this->getWizardData($request);
        $wizardData['step2']['disponentes'] = array_diff($wizardData['step2']['disponentes'], [$personId]);
        $this->updateWizardData($request, $wizardData);

        return redirect()->route('admin.tramites.wizard.create.step2');
    }

    public function postStep2(Request $request)
    {
        $wizardData = $this->getWizardData($request);
        if (empty($wizardData['step2']['disponentes'])) {
            return back()->withErrors('Debe agregar al menos un disponente.');
        }

        try {
            $this->validateWizardIntegrity($wizardData);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors($e->getMessage());
        }

        return redirect()->route('admin.tramites.wizard.create.step3');
    }

    // ==================== PASO 3: ADQUIRENTES ====================
    public function createStep3(Request $request)
    {
        $wizardData = $this->getWizardData($request);
        if (empty($wizardData['step2']['disponentes'])) {
            return redirect()->route('admin.tramites.wizard.create.step2');
        }

        $adquirentes = collect($wizardData['step3']['adquirentes'] ?? [])->map(function ($adq) {
            $person = Person::find($adq['person_id']);
            if ($person) {
                $person->parentesco_id = $adq['parentesco_id'];
                $person->parentesco_nombre = \App\Models\Parentesco::find($adq['parentesco_id'])->nombre ?? '';
                $person->porcentaje = $adq['porcentaje'] ?? 0;
            }

            return $person;
        })->filter();

        $beni = \App\Models\Departamento::where('codigo', \App\Models\Departamento::CODIGO_BENI)->first();
        $fechaPresentacion = $wizardData['step1']['fecha_presentacion'] ?? now()->toDateString();

        // Obtener parentescos con sus tasas vigentes
        $parentescos = Parentesco::with(['tasas' => function ($query) use ($beni, $fechaPresentacion) {
            if ($beni) {
                $query->where('departamento_id', $beni->id)
                    ->where('vigente_desde', '<=', $fechaPresentacion)
                    ->where(function ($q) use ($fechaPresentacion) {
                        $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $fechaPresentacion);
                    });
            }
        }])->get()->map(function ($parentesco) {
            $tasa = $parentesco->tasas->first();
            $parentesco->tasa_aplicable = $tasa ? $tasa->tasa : 0.00;

            return $parentesco;
        });

        // Agrupar parentescos para el selector visual
        $parentescosAgrupados = \App\Models\Parentesco::agruparParaSelect($parentescos);

        // Categorías simplificadas para selección rápida
        $categorias = \App\Models\Parentesco::agruparPorCategorias();

        $isEdit = ! empty($wizardData['current_tramite_id']);

        return view('admin.tramites.wizard.create_step_3', [
            'adquirentes' => $adquirentes,
            'parentescos' => $parentescos,
            'parentescosAgrupados' => $parentescosAgrupados,
            'categorias' => $categorias,
            'step_title' => 'Paso 3: Adquirentes',
            'current_step' => 3, 'total_steps' => 7, 'progress' => 42,
            'is_edit' => $isEdit,
            'current_tramite_id' => $wizardData['current_tramite_id'] ?? null,
        ]);
    }

    public function addAdquirente(Request $request)
    {
        $request->validate([
            'person_id' => 'required|exists:people,id',
            'parentesco_id' => 'required|exists:parentescos,id',
            'porcentaje' => 'required|numeric|min:0.01|max:100',
        ]);

        $wizardData = $this->getWizardData($request);
        $personId = $request->person_id;

        // Verificar que no esté ya como disponente
        if (in_array($personId, $wizardData['step2']['disponentes'] ?? [])) {
            return back()->withErrors('Esta persona ya está agregada como disponente.');
        }

        // Verificar que no esté ya como adquirente
        $existing = collect($wizardData['step3']['adquirentes'] ?? [])->first(function ($adq) use ($personId) {
            return $adq['person_id'] == $personId;
        });

        if (! $existing) {
            $wizardData['step3']['adquirentes'][] = [
                'person_id' => $personId,
                'parentesco_id' => $request->parentesco_id,
                'porcentaje' => $request->porcentaje,
            ];
            $this->updateWizardData($request, $wizardData);
        }

        return redirect()->route('admin.tramites.wizard.create.step3');
    }

    public function removeAdquirente(Request $request, $personId)
    {
        $wizardData = $this->getWizardData($request);
        $wizardData['step3']['adquirentes'] = array_filter($wizardData['step3']['adquirentes'] ?? [], function ($adq) use ($personId) {
            return $adq['person_id'] != $personId;
        });
        $this->updateWizardData($request, $wizardData);

        return redirect()->route('admin.tramites.wizard.create.step3');
    }

    public function postStep3(Request $request)
    {
        $wizardData = $this->getWizardData($request);
        if (empty($wizardData['step3']['adquirentes'])) {
            return back()->withErrors('Debe agregar al menos un adquirente.');
        }

        $totalPorcentaje = collect($wizardData['step3']['adquirentes'])->sum('porcentaje');
        if ($totalPorcentaje != 100) {
            return back()->withErrors("La suma de porcentajes de los adquirentes debe ser exactamente 100%. Actual: {$totalPorcentaje}%.");
        }

        try {
            $this->validateWizardIntegrity($wizardData);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors($e->getMessage());
        }

        return redirect()->route('admin.tramites.wizard.create.step4');
    }

    // ==================== PASO 4: INMUEBLES ====================
    public function createStep4(Request $request)
    {
        $wizardData = $this->getWizardData($request);
        if (empty($wizardData['step3']['adquirentes'])) {
            return redirect()->route('admin.tramites.wizard.create.step3');
        }

        $inmuebles = collect($wizardData['step4']['inmuebles'] ?? [])->filter(function ($inmuebleId) {
            if (! is_numeric($inmuebleId)) {
                return false;
            }

            return true;
        })->map(function ($inmuebleId) {
            return Inmueble::find($inmuebleId);
        })->filter();

        $isEdit = ! empty($wizardData['current_tramite_id']);

        return view('admin.tramites.wizard.create_step_4', [
            'inmuebles' => $inmuebles,
            'step_title' => 'Paso 4: Inmuebles',
            'current_step' => 4, 'total_steps' => 7, 'progress' => 57,
            'is_edit' => $isEdit,
            'current_tramite_id' => $wizardData['current_tramite_id'] ?? null,
        ]);
    }

    public function addInmueble(Request $request)
    {
        $request->validate(['inmueble_id' => 'required|exists:inmuebles,id']);

        $wizardData = $this->getWizardData($request);
        $inmuebleId = $request->inmueble_id;

        if (! in_array($inmuebleId, $wizardData['step4']['inmuebles'] ?? [])) {
            $wizardData['step4']['inmuebles'][] = $inmuebleId;
            $this->updateWizardData($request, $wizardData);
        }

        return redirect()->route('admin.tramites.wizard.create.step4');
    }

    public function removeInmueble(Request $request, $inmuebleId)
    {
        $wizardData = $this->getWizardData($request);
        $wizardData['step4']['inmuebles'] = array_diff($wizardData['step4']['inmuebles'] ?? [], [$inmuebleId]);
        $this->updateWizardData($request, $wizardData);

        return redirect()->route('admin.tramites.wizard.create.step4');
    }

    public function postStep4(Request $request)
    {
        $wizardData = $this->getWizardData($request);
        if (empty($wizardData['step4']['inmuebles'])) {
            return back()->withErrors('Debe agregar al menos un inmueble.');
        }

        try {
            $this->validateWizardIntegrity($wizardData);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors($e->getMessage());
        }

        return redirect()->route('admin.tramites.wizard.create.step5'); // Redirige al nuevo paso 5
    }

    // ==================== PASO 5: DOCUMENTOS (NUEVO) ====================
    public function createStep5(Request $request)
    {
        $wizardData = $this->getWizardData($request);
        if (empty($wizardData['step4']['inmuebles'])) {
            return redirect()->route('admin.tramites.wizard.create.step4');
        }

        $disponentesIds = $wizardData['step2']['disponentes'] ?? [];
        $adquirentesIds = collect($wizardData['step3']['adquirentes'] ?? [])->pluck('person_id')->all();
        $personas = Person::whereIn('id', array_merge($disponentesIds, $adquirentesIds))->get();

        // $tiposDocumento = ['Escritura', 'Testamento', 'Partida', 'CI', 'Avaluo', 'Poder', 'Otro'];
        $tiposDocumento = ['Declaratorio', 'Aceptación de Herencia', 'Sentencia', 'Minuta', 'Auto Avaluo', 'CI', 'DPF', 'Otro'];

        $documentosSubidos = collect($wizardData['step5']['documentos'] ?? [])->map(function ($doc) {
            $doc['persona'] = Person::find($doc['person_id']);

            return (object) $doc;
        });

        $isEdit = ! empty($wizardData['current_tramite_id']);

        return view('admin.tramites.wizard.create_step_5', [
            'documentosSubidos' => $documentosSubidos,
            'personas' => $personas,
            'tiposDocumento' => $tiposDocumento,
            'step_title' => 'Paso 5: Documentos de Respaldo',
            'current_step' => 5, 'total_steps' => 7, 'progress' => 71,
            'is_edit' => $isEdit,
            'current_tramite_id' => $wizardData['current_tramite_id'] ?? null,
        ]);
    }

    public function addDocumento(Request $request)
    {
        $request->validate([
            'tipo_doc' => 'required|string',
            'person_id' => 'required|exists:people,id',
            'archivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ]);

        $wizardData = $this->getWizardData($request);
        $file = $request->file('archivo');

        // Guardar archivo temporalmente
        $tempPath = $file->store('wizard_temp_docs', 'local');

        $wizardData['step5']['documentos'][] = [
            'id' => uniqid(), // ID temporal para poder borrarlo
            'tipo_doc' => $request->tipo_doc,
            'person_id' => $request->person_id,
            'temp_path' => $tempPath,
            'original_name' => $file->getClientOriginalName(),
        ];

        $this->updateWizardData($request, $wizardData);

        return redirect()->route('admin.tramites.wizard.create.step5');
    }

    public function removeDocumento(Request $request, $docId)
    {
        $wizardData = $this->getWizardData($request);
        $documentos = collect($wizardData['step5']['documentos'] ?? []);

        $documentoABorrar = $documentos->firstWhere('id', $docId);

        if ($documentoABorrar) {
            // Borrar el archivo temporal
            \Storage::disk('local')->delete($documentoABorrar['temp_path']);

            // Quitar de la sesión
            $wizardData['step5']['documentos'] = $documentos->where('id', '!=', $docId)->values()->all();
            $this->updateWizardData($request, $wizardData);
        }

        return redirect()->route('admin.tramites.wizard.create.step5');
    }

    public function postStep5(Request $request)
    {
        return redirect()->route('admin.tramites.wizard.create.step6');
    }

    // ==================== PASO 6: EXENCIONES (ANTES PASO 5) ====================
    public function createStep6(Request $request)
    {
        $wizardData = $this->getWizardData($request);
        if (empty($wizardData['step4']['inmuebles'])) {
            return redirect()->route('admin.tramites.wizard.create.step4');
        }

        // Bug #3: Usar fecha de presentación del trámite en lugar de now()
        $fechaPresentacion = $wizardData['step1']['fecha_presentacion'] ?? now()->toDateString();

        // Obtener exenciones NO seleccionadas
        $exencionesSeleccionadasIds = $wizardData['step6']['exenciones'] ?? [];
        $exencionesDisponibles = \App\Models\Exencion::withTrashed()
            ->whereNotIn('id', $exencionesSeleccionadasIds)
            ->orderBy('nombre')
            ->get();

        $exencionesSeleccionadas = \App\Models\Exencion::withTrashed()
            ->whereIn('id', $exencionesSeleccionadasIds)
            ->get();

        $isEdit = ! empty($wizardData['current_tramite_id']);

        return view('admin.tramites.wizard.create_step_6', [
            'exencionesSeleccionadas' => $exencionesSeleccionadas,
            'exencionesDisponibles' => $exencionesDisponibles,
            'step_title' => 'Paso 6: Exenciones Aplicables',
            'current_step' => 6, 'total_steps' => 7, 'progress' => 85,
            'is_edit' => $isEdit,
            'current_tramite_id' => $wizardData['current_tramite_id'] ?? null,
        ]);
    }

    public function addExencion(Request $request)
    {
        $request->validate(['exencion_id' => 'required|exists:exenciones,id']);

        $wizardData = $this->getWizardData($request);
        $exencionId = $request->exencion_id;

        if (! in_array($exencionId, $wizardData['step6']['exenciones'] ?? [])) {
            $wizardData['step6']['exenciones'][] = $exencionId;
            $this->updateWizardData($request, $wizardData);
        }

        return redirect()->route('admin.tramites.wizard.create.step6');
    }

    public function removeExencion(Request $request, $exencionId)
    {
        $wizardData = $this->getWizardData($request);
        $wizardData['step6']['exenciones'] = array_diff($wizardData['step6']['exenciones'] ?? [], [$exencionId]);
        $this->updateWizardData($request, $wizardData);

        return redirect()->route('admin.tramites.wizard.create.step6');
    }

    public function postStep6(Request $request)
    {
        // Este paso es opcional, así que no se valida si hay exenciones o no.
        // Simplemente pasamos al siguiente paso.
        return redirect()->route('admin.tramites.wizard.create.step7');
    }

    // ==================== PASO 7: RESUMEN Y GUARDAR ====================
    public function createStep7(Request $request)
    {
        $wizardData = $this->getWizardData($request);

        if (empty($wizardData['step1']) || empty($wizardData['step3']['adquirentes']) || empty($wizardData['step4']['inmuebles'])) {
            return redirect()->route('admin.tramites.wizard.create.step1');
        }

        $calculator = app(IdtgbCalculator::class);

        $inmuebleRef = Inmueble::with('municipio.provincia')->find($wizardData['step4']['inmuebles'][0]);
        $depId = $inmuebleRef->municipio->provincia->departamento_id;

        $adquirentesData = collect($wizardData['step3']['adquirentes'])->map(function ($adq) {
            return [
                'parentesco_id' => $adq['parentesco_id'],
                'porcentaje' => $adq['porcentaje'],
            ];
        })->all();

        $fechaVencimiento = Carbon::parse($wizardData['step1']['fecha_presentacion'])->addDays(30);
        $exencionesData = [];

        $liquidacion = $calculator->performCalculation(
            $wizardData['step1']['base_imponible'],
            $depId,
            $wizardData['step1']['tipo_transmision_id'],
            now()->toDateString(),
            $wizardData['step1']['fecha_transmision'],
            $fechaVencimiento->toDateString(),
            $adquirentesData,
            $exencionesData,
            'Natural',
            100
        );

        return $this->showSummary($request, $wizardData, $liquidacion);
    }

    private function showSummary(Request $request, array $wizardData, $liquidacion = null)
    {
        // Validar datos completos
        // Esta validación ya se hace en createStep6, no es necesaria aquí.

        if (empty($wizardData['step1']) ||
            empty($wizardData['step2']['disponentes']) ||
            empty($wizardData['step3']['adquirentes']) ||
            empty($wizardData['step4']['inmuebles'])) { // La validación de exenciones es opcional
            return redirect()->route('admin.tramites.wizard.create.step1');
        }

        try {
            // Cargar datos para el resumen con manejo de errores
            $disponentes = Person::with([
                'municipio.provincia.departamento',
            ])->whereIn('id', $wizardData['step2']['disponentes'])->get();

            $adquirentes = Person::with([
                'municipio.provincia.departamento',
            ])->whereIn('id', collect($wizardData['step3']['adquirentes'])->pluck('person_id'))->get();

            $inmuebles = Inmueble::with([
                'tipoInmueble',
                'municipio.provincia.departamento',
            ])->whereIn('id', $wizardData['step4']['inmuebles'])->get();

            // Cargar exenciones para el resumen
            $exenciones = \App\Models\Exencion::whereIn('id', $wizardData['step6']['exenciones'] ?? [])->get();

            // Cargar documentos para el resumen
            $documentos = collect($wizardData['step5']['documentos'] ?? [])->map(function ($doc) {
                $doc['persona'] = Person::find($doc['person_id']);

                return (object) $doc;
            });

            // Agregar parentesco a adquirentes
            $adquirentes = $adquirentes->map(function ($adq) use ($wizardData) {
                try {
                    $adqData = collect($wizardData['step3']['adquirentes'])->firstWhere('person_id', $adq->id);
                    $adq->parentesco_id = $adqData['parentesco_id'] ?? null;
                    $adq->parentesco_nombre = Parentesco::find($adqData['parentesco_id'])->nombre ?? 'No especificado';
                } catch (\Exception $e) {
                    $adq->parentesco_nombre = 'No especificado';
                }

                return $adq;
            });

            $tipoTransmision = \App\Models\TipoTransmision::find($wizardData['step1']['tipo_transmision_id']);

            $isEdit = ! empty($wizardData['current_tramite_id']);

            return view('admin.tramites.wizard.create_step_7', [
                'wizardData' => $wizardData,
                'disponentes' => $disponentes,
                'adquirentes' => $adquirentes,
                'inmuebles' => $inmuebles,
                'documentos' => $documentos,
                'exenciones' => $exenciones,
                'tipoTransmision' => $tipoTransmision,
                'liquidacion' => $liquidacion,
                'step_title' => 'Paso 7: Resumen y Confirmación',
                'current_step' => 7,
                'total_steps' => 7,
                'progress' => 100,
                'is_edit' => $isEdit,
                'current_tramite_id' => $wizardData['current_tramite_id'] ?? null,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en createStep5: '.$e->getMessage());

            return redirect()->route('admin.tramites.wizard.create.step1')
                ->withErrors('Error al cargar el resumen: '.$e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $wizardData = $this->getWizardData($request);

        // Validación final
        if (empty($wizardData['step1']) ||
            empty($wizardData['step2']['disponentes']) ||
            empty($wizardData['step3']['adquirentes']) ||
            empty($wizardData['step4']['inmuebles'])) { // Exenciones son opcionales
            return redirect()->route('admin.tramites.wizard.create.step1')
                ->withErrors('Datos incompletos. Por favor, complete todos los pasos.');
        }

        try {
            $this->validateWizardIntegrity($wizardData);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('admin.tramites.wizard.create.step7')
                ->withErrors($e->getMessage());
        }

        try {
            DB::beginTransaction();

            $currentId = $wizardData['current_tramite_id'] ?? null;
            $tramiteData = array_merge($wizardData['step1'], [
                'user_id' => auth()->id(),
                'updated_by' => auth()->id(),
                'fecha_vencimiento' => Carbon::parse($wizardData['step1']['fecha_presentacion'])->addDays(30),
            ]);

            if ($currentId) {
                $tramite = Tramite::findOrFail($currentId);
                $tramite->update($tramiteData);

                $tramite->disponentes()->delete();
                $tramite->adquirentes()->delete();
                $tramite->tramiteExenciones()->delete();
            } else {
                $tramiteData['created_by'] = auth()->id();
                $tramiteData['estado'] = 'Borrador';
                $tramiteData['ufv_aplicada'] = 1.00000;
                $tramite = Tramite::create($tramiteData);
            }

            // 2. Asociar disponentes
            foreach ($wizardData['step2']['disponentes'] as $personId) {
                $tramite->disponentes()->create([
                    'person_id' => $personId,
                    'tipo' => 'Donante', // Valor por defecto, se puede ajustar
                ]);
            }

            // 3. Asociar adquirentes
            foreach ($wizardData['step3']['adquirentes'] as $adqData) {
                // dd($adqData);
                $tramite->adquirentes()->create([
                    'person_id' => $adqData['person_id'],
                    'parentesco_id' => $adqData['parentesco_id'],
                    'tasa_aplicada' => 0,
                    'porcentaje' => $adqData['porcentaje'] ?? 0,
                    'idtgb_proporcional' => 0,
                    'es_beneficiario_exencion' => false,
                ]);
            }

            // 4. Asociar inmuebles
            $tramite->inmuebles()->sync($wizardData['step4']['inmuebles']);

            // Es crucial recargar la relación para que esté disponible en el objeto $tramite
            $tramite->load('inmuebles');

            // 5. Guardar y asociar documentos
            $documentosMantenidosIds = [];

            if (! empty($wizardData['step5']['documentos'])) {
                foreach ($wizardData['step5']['documentos'] as $docData) {
                    if (! empty($docData['existing'])) {
                        $documentosMantenidosIds[] = $docData['id'];

                        continue;
                    }

                    $tempPath = $docData['temp_path'];
                    $finalPath = str_replace('wizard_temp_docs', "tramites/{$tramite->id}/documentos", $tempPath);

                    // VERIFICACIÓN: ¿Existe el archivo en el disco local?
                    if (\Storage::disk('local')->exists($tempPath)) {

                        // 1. Leer el contenido
                        $fileContents = \Storage::disk('local')->get($tempPath);

                        if ($fileContents !== null) {
                            // 2. Escribir en el disco público
                            \Storage::disk('public')->put($finalPath, $fileContents);

                            // 3. Borrar el temporal
                            \Storage::disk('local')->delete($tempPath);

                            // Calcular hash usando la ruta absoluta del nuevo destino
                            $fullPath = \Storage::disk('public')->path($finalPath);
                            $hash = hash_file('sha256', $fullPath);

                            $version = \App\Models\Documento::where('tramite_id', $tramite->id)
                                ->where('tipo_doc', $docData['tipo_doc'])
                                ->max('version') + 1;

                            // Marcar versiones anteriores como no vigentes
                            \App\Models\Documento::where('tramite_id', $tramite->id)
                                ->where('tipo_doc', $docData['tipo_doc'])
                                ->update(['vigente' => false]);

                            $newDoc = $tramite->documentos()->create([
                                'tipo_doc' => $docData['tipo_doc'],
                                'person_id' => $docData['person_id'],
                                'archivo_path' => $finalPath,
                                'hash_sha256' => $hash,
                                'version' => $version,
                                'vigente' => true,
                                'original_name' => $docData['original_name'] ?? 'documento.pdf',
                            ]);
                            $documentosMantenidosIds[] = $newDoc->id;
                        }
                    } else {
                        // OPCIONAL: Registrar en el log si el archivo no se encontró
                        \Log::warning('El archivo temporal no se encontró en: '.$tempPath);

                        // Si el archivo ya existe en el destino (por un reintento), podrías decidir
                        // si crear el registro en BD o saltarlo.
                    }
                }
            }

            if ($currentId) {
                $tramite->documentos()
                    ->whereNotIn('id', $documentosMantenidosIds)
                    ->where('vigente', true)
                    ->update(['vigente' => false]);
            }

            // 6. Asociar exenciones (si las hay) - Bug #6: Calcular monto correcto
            if (! empty($wizardData['step6']['exenciones'])) {
                foreach ($wizardData['step6']['exenciones'] as $exencionId) {
                    $exencion = \App\Models\Exencion::find($exencionId);
                    if ($exencion) {
                        // Calcular el monto según el tipo de exención
                        $baseImponible = $wizardData['step1']['base_imponible'] ?? 0;
                        $montoCalculado = 0;

                        if ($exencion->tipo === 'porcentaje') {
                            $montoCalculado = ($baseImponible * $exencion->valor) / 100;
                        } elseif ($exencion->tipo === 'monto_fijo') {
                            $montoCalculado = $exencion->valor;
                        }

                        // Aplicar monto máximo si existe
                        if ($exencion->monto_maximo !== null && $montoCalculado > $exencion->monto_maximo) {
                            $montoCalculado = $exencion->monto_maximo;
                        }

                        $tramite->tramiteExenciones()->create([
                            'exencion_id' => $exencionId,
                            'monto_aplicado' => round($montoCalculado, 2),
                        ]);
                    }
                }
            }

            $tramite->load('adquirentes', 'inmuebles');

            // 6. REALIZAR EL CÁLCULO DESPUÉS DE QUE TODO ESTÉ GUARDADO
            // Pausar los eventos del modelo para evitar un bucle infinito con el observador
            $tramite->withoutEvents(function () use ($tramite) {
                app(IdtgbCalculator::class)->calculateAndSave($tramite);
            });

            DB::commit();

            // Limpiar sesión
            $request->session()->forget($this->getSessionKey());

            $accion = $currentId ? 'actualizado' : 'creado';

            return redirect()->route('admin.tramites.show', $tramite)
                ->with([
                    'message' => "Trámite #{$tramite->nro_tramite} {$accion} exitosamente",
                    'alert-type' => 'success',
                ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('admin.tramites.wizard.create.step7')
                ->withErrors('Error al guardar el trámite: '.$e->getMessage());
        }
    }

    public function cancelWizard(Request $request)
    {
        $wizardData = $this->getWizardData($request);

        // Mover documentos temporales a carpeta de cancelados en lugar de borrarlos
        if (! empty($wizardData['step5']['documentos'])) {
            foreach ($wizardData['step5']['documentos'] as $docData) {
                $tempPath = $docData['temp_path'] ?? null;
                if ($tempPath && \Storage::disk('local')->exists($tempPath)) {
                    $cancelledPath = str_replace('wizard_temp_docs', 'wizard_cancelled', $tempPath);
                    \Storage::disk('local')->move($tempPath, $cancelledPath);
                    \Log::info('Documento temporal movido a cancelados', [
                        'original' => $tempPath,
                        'nuevo' => $cancelledPath,
                    ]);
                }
            }
        }

        $request->session()->forget($this->getSessionKey());

        return redirect()->route('admin.tramites.index')
            ->with([
                'message' => 'Creación de trámite cancelada. Los documentos han sido guardados temporalmente.',
                'alert-type' => 'info',
            ]);
    }

    public function ajaxPersonList(Request $request)
    {
        $term = $request->get('q', '');

        $people = Person::where(function ($query) use ($term) {
            $query->where('nombre_completo', 'LIKE', "%{$term}%")
                ->orWhere('ci', 'LIKE', "%{$term}%")
                ->orWhere('legal_name', 'LIKE', "%{$term}%")
                ->orWhere('nit', 'LIKE', "%{$term}%");
        })
            ->limit(20)
            ->get(['id', 'nombre_completo', 'legal_name', 'ci', 'person_type', 'nit', 'tipo_doc']);

        $formatted = $people->map(function ($person) {
            return [
                'id' => $person->id,
                'text' => $person->display_name.' - '.($person->person_type === 'Jurídica' ? 'NIT: '.$person->nit : 'CI: '.$person->ci),
                'person_type' => $person->person_type, // Incluir el tipo de persona
                'document' => $person->display_document, // Incluir el documento formateado
            ];
        });

        return response()->json(['results' => $formatted]);
    }
}
