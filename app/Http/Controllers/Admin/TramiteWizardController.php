<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inmueble;
use App\Models\Person;
use App\Models\Parentesco;
use App\Models\TipoTransmision;
use App\Models\Tramite;
use App\Services\IdtgbCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TramiteWizardController extends Controller
{
    private function getSessionKey()
    {
        return 'tramite_wizard_data';
    }

    private function initializeWizard(Request $request)
    {
        if (!$request->session()->has($this->getSessionKey())) {
            $request->session()->put($this->getSessionKey(), [
                'step1' => [],
                'step2' => ['disponentes' => []],
                'step3' => ['adquirentes' => []],
                'step4' => ['inmuebles' => []],
                'current_tramite_id' => null
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

    // ==================== PASO 1: DATOS GENERALES ====================
    public function createStep1(Request $request)
    {
        $this->initializeWizard($request);
        $wizardData = $this->getWizardData($request);
        $tipos = TipoTransmision::all();

        return view('admin.tramites.wizard.create_step_1', [
            'tipos' => $tipos,
            'data' => $wizardData['step1'],
            'step_title' => 'Paso 1: Datos Generales',
            'current_step' => 1, 'total_steps' => 5, 'progress' => 20,
        ]);
    }

    public function postStep1(Request $request)
    {
        $validated = $request->validate([
            'nro_tramite' => 'required|string|max:15|unique:tramites,nro_tramite',
            'fecha_presentacion' => 'required|date',
            'fecha_transmision' => 'required|date',
            'tipo_transmision_id' => 'required|exists:tipos_transmision,id',
            'valor_declarado' => 'required|numeric|min:0',
            'base_imponible' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $wizardData = $this->getWizardData($request);
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

        $disponentes = collect($wizardData['step2']['disponentes'])->map(function($personId) {
            return Person::find($personId);
        })->filter();

        return view('admin.tramites.wizard.create_step_2', [
            'disponentes' => $disponentes,
            'step_title' => 'Paso 2: Disponentes',
            'current_step' => 2, 'total_steps' => 5, 'progress' => 40,
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
        if (!in_array($personId, $wizardData['step2']['disponentes'])) {
            $wizardData['step2']['disponentes'][] = $personId;
            $this->updateWizardData($request, $wizardData);
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

        return redirect()->route('admin.tramites.wizard.create.step3');
    }

    // ==================== PASO 3: ADQUIRENTES ====================
    public function createStep3(Request $request)
    {
        $wizardData = $this->getWizardData($request);
        if (empty($wizardData['step2']['disponentes'])) {
            return redirect()->route('admin.tramites.wizard.create.step2');
        }

        $adquirentes = collect($wizardData['step3']['adquirentes'] ?? [])->map(function($adq) {
            $person = Person::find($adq['person_id']);
            if ($person) {
                $person->parentesco_id = $adq['parentesco_id'];
                $person->parentesco_nombre = \App\Models\Parentesco::find($adq['parentesco_id'])->nombre ?? '';
            }
            return $person;
        })->filter();

        $parentescos = Parentesco::all();

        return view('admin.tramites.wizard.create_step_3', [
            'adquirentes' => $adquirentes,
            'parentescos' => $parentescos,
            'step_title' => 'Paso 3: Adquirentes',
            'current_step' => 3, 'total_steps' => 5, 'progress' => 60,
        ]);
    }

    public function addAdquirente(Request $request)
    {
        $request->validate([
            'person_id' => 'required|exists:people,id',
            'parentesco_id' => 'required|exists:parentescos,id',
        ]);

        $wizardData = $this->getWizardData($request);
        $personId = $request->person_id;

        // Verificar que no esté ya como disponente
        if (in_array($personId, $wizardData['step2']['disponentes'] ?? [])) {
            return back()->withErrors('Esta persona ya está agregada como disponente.');
        }

        // Verificar que no esté ya como adquirente
        $existing = collect($wizardData['step3']['adquirentes'] ?? [])->first(function($adq) use ($personId) {
            return $adq['person_id'] == $personId;
        });

        if (!$existing) {
            $wizardData['step3']['adquirentes'][] = [
                'person_id' => $personId,
                'parentesco_id' => $request->parentesco_id
            ];
            $this->updateWizardData($request, $wizardData);
        }

        return redirect()->route('admin.tramites.wizard.create.step3');
    }

    public function removeAdquirente(Request $request, $personId)
    {
        $wizardData = $this->getWizardData($request);
        $wizardData['step3']['adquirentes'] = array_filter($wizardData['step3']['adquirentes'] ?? [], function($adq) use ($personId) {
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

        return redirect()->route('admin.tramites.wizard.create.step4');
    }

    // ==================== PASO 4: INMUEBLES ====================
    public function createStep4(Request $request)
    {
        $wizardData = $this->getWizardData($request);
        if (empty($wizardData['step3']['adquirentes'])) {
            return redirect()->route('admin.tramites.wizard.create.step3');
        }

        $inmuebles = collect($wizardData['step4']['inmuebles'] ?? [])->map(function($inmuebleId) {
            return Inmueble::find($inmuebleId);
        })->filter();

        return view('admin.tramites.wizard.create_step_4', [
            'inmuebles' => $inmuebles,
            'step_title' => 'Paso 4: Inmuebles',
            'current_step' => 4, 'total_steps' => 5, 'progress' => 80,
        ]);
    }

    public function addInmueble(Request $request)
    {
        $request->validate(['inmueble_id' => 'required|exists:inmuebles,id']);

        $wizardData = $this->getWizardData($request);
        $inmuebleId = $request->inmueble_id;

        if (!in_array($inmuebleId, $wizardData['step4']['inmuebles'] ?? [])) {
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

        return redirect()->route('admin.tramites.wizard.create.step5');
    }

    // ==================== PASO 5: RESUMEN Y GUARDAR ====================
    public function createStep5(Request $request)
    {
        $wizardData = $this->getWizardData($request);

        // Validar datos completos
        if (empty($wizardData['step1']) ||
            empty($wizardData['step2']['disponentes']) ||
            empty($wizardData['step3']['adquirentes']) ||
            empty($wizardData['step4']['inmuebles'])) {
            return redirect()->route('admin.tramites.wizard.create.step1');
        }

        try {
            // Cargar datos para el resumen con manejo de errores
            $disponentes = Person::with([
                'municipio.provincia.departamento'
            ])->whereIn('id', $wizardData['step2']['disponentes'])->get();

            $adquirentes = Person::with([
                'municipio.provincia.departamento'
            ])->whereIn('id', collect($wizardData['step3']['adquirentes'])->pluck('person_id'))->get();

            $inmuebles = Inmueble::with([
                'tipoInmueble',
                'municipio.provincia.departamento'
            ])->whereIn('id', $wizardData['step4']['inmuebles'])->get();

            // Agregar parentesco a adquirentes
            $adquirentes = $adquirentes->map(function($adq) use ($wizardData) {
                try {
                    $adqData = collect($wizardData['step3']['adquirentes'])->firstWhere('person_id', $adq->id);
                    $adq->parentesco_id = $adqData['parentesco_id'] ?? null;
                    $adq->parentesco_nombre = Parentesco::find($adqData['parentesco_id'])->nombre ?? 'No especificado';
                } catch (\Exception $e) {
                    $adq->parentesco_nombre = 'No especificado';
                }
                return $adq;
            });

            // Cargar datos adicionales para el resumen
            $tipoTransmision = \App\Models\TipoTransmision::find($wizardData['step1']['tipo_transmision_id']);

            return view('admin.tramites.wizard.create_step_5', [
                'wizardData' => $wizardData,
                'disponentes' => $disponentes,
                'adquirentes' => $adquirentes,
                'inmuebles' => $inmuebles,
                'tipoTransmision' => $tipoTransmision,
                'step_title' => 'Paso 5: Resumen y Confirmación',
                'current_step' => 5,
                'total_steps' => 5,
                'progress' => 100,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en createStep5: ' . $e->getMessage());
            return redirect()->route('admin.tramites.wizard.create.step1')
                ->withErrors('Error al cargar el resumen: ' . $e->getMessage());
        }
    }
    public function store(Request $request)
    {
        $wizardData = $this->getWizardData($request);

        // Validación final
        if (empty($wizardData['step1']) ||
            empty($wizardData['step2']['disponentes']) ||
            empty($wizardData['step3']['adquirentes']) ||
            empty($wizardData['step4']['inmuebles'])) {
            return redirect()->route('admin.tramites.wizard.create.step1')
                ->withErrors('Datos incompletos. Por favor, complete todos los pasos.');
        }

        try {
            DB::beginTransaction();

            // 1. Crear trámite principal
            $tramite = Tramite::create(array_merge($wizardData['step1'], [
                'user_id' => auth()->id(),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
                'fecha_vencimiento' => Carbon::parse($wizardData['step1']['fecha_presentacion'])->addDays(30),
                'estado' => 'Borrador',
                'ufv_aplicada' => 1.00000, // Valor por defecto
            ]));

            // 2. Asociar disponentes
            foreach ($wizardData['step2']['disponentes'] as $personId) {
                $tramite->disponentes()->create([
                    'person_id' => $personId,
                    'tipo' => 'Donante' // Valor por defecto, se puede ajustar
                ]);
            }

            // 3. Asociar adquirentes
            foreach ($wizardData['step3']['adquirentes'] as $adqData) {
                // dd($adqData);
                $tramite->adquirentes()->create([
                    'person_id' => $adqData['person_id'],
                    'parentesco_id' => $adqData['parentesco_id'],
                    'tasa_aplicada' => 0,
                    // 'porcentaje' => 0,
                    'porcentaje' => $adqData['porcentaje'] ?? 0, // <--- ¡SOLUCIÓN! Usar el valor de la sesión.
                    'idtgb_proporcional' => 0,
                    'es_beneficiario_exencion' => false
                ]);
            }

            // 4. Asociar inmuebles
            $tramite->inmuebles()->sync($wizardData['step4']['inmuebles']);

            // Es crucial recargar la relación para que esté disponible en el objeto $tramite
            $tramite->load('inmuebles');

            // 5. El observador se encargará de calcular los impuestos automáticamente

            // 5. Cargar las relaciones para que el servicio tenga acceso a ellas
            $tramite->load('adquirentes', 'inmuebles');

            // 6. REALIZAR EL CÁLCULO DESPUÉS DE QUE TODO ESTÉ GUARDADO
            // Pausar los eventos del modelo para evitar un bucle infinito con el observador
            $tramite->withoutEvents(function () use ($tramite) {
                app(IdtgbCalculator::class)->calculateAndSave($tramite);
            });


            DB::commit();

            // Limpiar sesión
            $request->session()->forget($this->getSessionKey());

            return redirect()->route('admin.tramites.show', $tramite)
                ->with([
                    'message' => "Trámite #{$tramite->nro_tramite} creado exitosamente",
                    'alert-type' => 'success'
                ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.tramites.wizard.create.step5')
                ->withErrors('Error al guardar el trámite: ' . $e->getMessage());
        }
    }

    public function cancelWizard(Request $request)
    {
        $request->session()->forget($this->getSessionKey());

        return redirect()->route('admin.tramites.index')
            ->with([
                'message' => 'Creación de trámite cancelada',
                'alert-type' => 'info'
            ]);
    }

    public function ajaxPersonList(Request $request)
    {
        $term = $request->get('q', '');

        $people = Person::where(function($query) use ($term) {
                $query->where('first_name', 'LIKE', "%{$term}%")
                      ->orWhere('paternal_surname', 'LIKE', "%{$term}%")
                      ->orWhere('ci', 'LIKE', "%{$term}%")
                      ->orWhere('legal_name', 'LIKE', "%{$term}%");
            })
            ->limit(20)
            ->get(['id', 'first_name', 'middle_name', 'paternal_surname', 'maternal_surname', 'legal_name', 'ci', 'person_type']);

        $formatted = $people->map(function($person) {
            return [
                'id' => $person->id,
                'text' => $person->display_name . ' - ' . ($person->person_type === 'Jurídica' ? 'NIT: ' . $person->nit : 'CI: ' . $person->ci)
            ];
        });

        return response()->json(['results' => $formatted]);
    }
}
