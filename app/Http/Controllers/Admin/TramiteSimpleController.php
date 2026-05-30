<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Departamento;
use App\Models\Inmueble;
use App\Models\Parentesco;
use App\Models\Person;
use App\Models\TipoTransmision;
use App\Models\Tramite;
use App\Services\IdtgbCalculator;
use App\Services\DiasHabilesService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TramiteSimpleController extends Controller
{
    public function create()
    {
        $tiposTransmision = TipoTransmision::select('id', 'nombre')->get();
        $inmuebles = Inmueble::select('id', 'catastro', 'direccion', 'municipio_id')
            ->with(['municipio.provincia.departamento:id,nombre,codigo'])
            ->get();
        $parentescos = Parentesco::select('id', 'nombre', 'categoria_tasa')->get();
        $beni = Departamento::select('id', 'nombre', 'codigo')->where('codigo', 'BE')->first();

        // Obtener parentescos agrupados por categorías
        $categorias = Parentesco::getCategoriasParaSelect();

        return view('admin.tramites.simple.create', [
            'tiposTransmision' => $tiposTransmision,
            'inmuebles' => $inmuebles,
            'parentescos' => $parentescos,
            'categorias' => $categorias,
            'beni' => $beni,
        ]);
    }

    public function store(Request $request)
    {
        Log::info('STORE: Iniciando creación de trámite');
        
        $request->validate([
            'nro_tramite' => 'nullable|string|max:15|unique:tramites,nro_tramite',
            'fecha_presentacion' => 'required|date',
            'fecha_transmision' => 'required|date|before_or_equal:fecha_presentacion',
            'tipo_transmision_id' => 'required|exists:tipos_transmision,id',
            'base_imponible' => 'required|numeric|min:0',
            'adquirente_id' => 'required|exists:people,id',
            'categoria_tasa' => 'required|in:1,10,20',
            'tipo_contribuyente' => 'required|in:Natural,Jurídica',
            'porcentaje' => 'required|numeric|min:1|max:100',
            'observaciones' => 'nullable|string|max:500',
        ]);

        Log::info('STORE: Validación pasada');

        try {
            DB::beginTransaction();
            Log::info('STORE: Transacción iniciada');

            // Usar departamento Beni directamente (código BE)
            Log::info('STORE: Buscando departamento Beni');
            $beni = Departamento::select('id', 'nombre', 'codigo')->where('codigo', 'BE')->first();
            $departamentoId = $beni->id;
            Log::info('STORE: Departamento Beni encontrado, ID: ' . $departamentoId);

            // Calcular fecha de vencimiento según tipo de transmisión (Ley 812)
            $fechaTransmision = Carbon::parse($request->fecha_transmision);
            $tipoTransmision = TipoTransmision::find($request->tipo_transmision_id);
            $diasHabilesService = app(DiasHabilesService::class);
            
            if ($tipoTransmision && strtolower($tipoTransmision->nombre) === 'mortis causa') {
                // Sucesiones hereditarias: 90 días calendario
                $fechaVencimiento = $fechaTransmision->copy()->addDays(90);
            } else {
                // Donaciones (Entre vivos): 5 días hábiles (excluyendo sábados, domingos y feriados)
                $fechaVencimiento = $diasHabilesService->calcularVencimiento(
                    $fechaTransmision,
                    5,
                    $departamentoId
                );
            }

            // Crear trámite
            Log::info('STORE: Creando trámite');
            $tramite = Tramite::create([
                'nro_tramite' => $request->nro_tramite,
                'fecha_presentacion' => $request->fecha_presentacion,
                'fecha_transmision' => $request->fecha_transmision,
                'fecha_vencimiento' => $fechaVencimiento,
                'tipo_transmision_id' => $request->tipo_transmision_id,
                'base_imponible' => $request->base_imponible,
                'valor_declarado' => $request->base_imponible,
                'observaciones' => $request->observaciones,
                'estado' => 'Borrador',
                'ufv_aplicada' => 1.00000,
                'total_idtgb' => 0,
                'recargo_mora' => 0,
                'monto_final' => 0,
                'user_id' => auth()->id(),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
            Log::info('STORE: Trámite creado, ID: ' . $tramite->id);

            // Asociar adquirente
            Log::info('STORE: Creando adquirente');
            
            // Obtener parentesco_id a partir de categoria_tasa
            $parentesco = Parentesco::getPrimerParentescoPorCategoria((int) $request->categoria_tasa);
            
            if (!$parentesco) {
                throw new \Exception('No se encontró parentesco para la categoría de tasa seleccionada: ' . $request->categoria_tasa);
            }
            
            $adquirente = $tramite->adquirentes()->create([
                'person_id' => $request->adquirente_id,
                'parentesco_id' => $parentesco->id,
                'porcentaje' => $request->porcentaje,
                'tasa_aplicada' => 0,
                'idtgb_proporcional' => 0,
                'es_beneficiario_exencion' => false,
            ]);
            Log::info('STORE: Adquirente creado, ID: ' . $adquirente->id);

            // Usar el mismo cálculo que la calculadora pública (performCalculation)
            Log::info('STORE: Iniciando cálculo');
            $calculator = app(IdtgbCalculator::class);
            
            $adquirentesData = [[
                'parentesco_id' => $parentesco->id,
                'porcentaje' => $request->porcentaje,
            ]];

            Log::info('STORE: Llamando a performCalculation');
            $resultados = $calculator->performCalculation(
                $request->base_imponible,
                $departamentoId,
                $request->tipo_transmision_id,
                Carbon::now()->toDateString(),
                $request->fecha_transmision,
                $fechaVencimiento->toDateString(),
                $adquirentesData,
                [],
                $request->tipo_contribuyente,
                $request->porcentaje
            );
            Log::info('STORE: Cálculo completado');

            // Actualizar trámite con resultados (sin eventos para evitar bucle infinito en observer)
            Log::info('STORE: Actualizando trámite con resultados');
            $tramite->withoutEvents(function () use ($tramite, $resultados) {
                $tramite->update([
                    'total_idtgb' => $resultados['idtgb_base'],
                    'tributo_actualizado' => $resultados['tributo_actualizado'],
                    'recargo_mora' => $resultados['interes'],
                    'multa_idf' => $resultados['multa_idf'],
                    'monto_final' => $resultados['final'],
                    'ufv_aplicada' => $resultados['ufv_pago'],
                    'ufv_vencimiento' => $resultados['ufv_vencimiento'],
                    'dias_mora' => $resultados['dias_mora'],
                    'categoria_tasa' => $resultados['categoria_tasa'],
                ]);
            });
            Log::info('STORE: Trámite actualizado');

            // Actualizar adquirente con tasa calculada
            Log::info('STORE: Buscando tasa vigente');
            $tasaModel = $calculator->tasaVigente(
                $departamentoId,
                $parentesco->id,
                $request->tipo_transmision_id,
                Carbon::now()->toDateString()
            );
            Log::info('STORE: Tasa vigente encontrada');

            $tasaVal = $tasaModel ? $tasaModel->tasa : 0;
            $baseSujeto = $request->base_imponible * ($request->porcentaje / 100);

            $adquirente->update([
                'tasa_aplicada' => $tasaVal,
                'idtgb_proporcional' => round($baseSujeto * ($tasaVal / 100), 2)
            ]);
            Log::info('STORE: Adquirente actualizado');

            DB::commit();
            Log::info('STORE: Transacción commit');

            return redirect()->route('admin.tramites.show', $tramite->id)
                ->with(['message' => 'Trámite creado exitosamente.', 'alert-type' => 'success']);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('STORE: Error - ' . $e->getMessage());
            return back()->withInput()
                ->with(['message' => 'Error al crear el trámite: ' . $e->getMessage(), 'alert-type' => 'error']);
        }
    }

    // ──────────────── EDICIÓN ────────────────
    public function edit(Tramite $tramite)
    {
        $tiposTransmision = TipoTransmision::select('id', 'nombre')->get();
        $inmuebles = Inmueble::select('id', 'catastro', 'direccion', 'municipio_id')
            ->with(['municipio.provincia.departamento:id,nombre,codigo'])
            ->get();
        $parentescos = Parentesco::select('id', 'nombre', 'categoria_tasa')->get();
        $beni = Departamento::select('id', 'nombre', 'codigo')->where('codigo', 'BE')->first();

        // Obtener parentescos agrupados por categorías
        $categorias = Parentesco::getCategoriasParaSelect();

        // Cargar datos del trámite existente
        $tramite->load(['adquirentes.person', 'adquirentes.parentesco']);

        return view('admin.tramites.simple.edit', [
            'tiposTransmision' => $tiposTransmision,
            'inmuebles' => $inmuebles,
            'parentescos' => $parentescos,
            'categorias' => $categorias,
            'beni' => $beni,
            'tramite' => $tramite,
        ]);
    }

    public function update(Request $request, Tramite $tramite)
    {
        Log::info('UPDATE: Iniciando actualización de trámite', ['tramite_id' => $tramite->id]);

        $request->validate([
            'nro_tramite' => 'nullable|string|max:15|unique:tramites,nro_tramite,' . $tramite->id,
            'fecha_presentacion' => 'required|date',
            'fecha_transmision' => 'required|date|before_or_equal:fecha_presentacion',
            'tipo_transmision_id' => 'required|exists:tipos_transmision,id',
            'base_imponible' => 'required|numeric|min:0',
            'categoria_tasa' => 'required|integer|in:1,10,20',
            'tipo_contribuyente' => 'required|string|in:Natural,Jurídica',
            'porcentaje' => 'required|integer|min:1|max:100',
            'person_id' => 'required|exists:people,id',
            'observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            Log::info('UPDATE: Transacción iniciada');

            // Calcular fecha de vencimiento
            $diasHabilesService = app(DiasHabilesService::class);
            $fechaVencimiento = $diasHabilesService->calcularFechaVencimiento(
                $request->fecha_transmision,
                $request->tipo_transmision_id,
                'BE'
            );
            Log::info('UPDATE: Fecha vencimiento calculada', ['fecha_vencimiento' => $fechaVencimiento]);

            // Actualizar trámite
            $tramite->update([
                'fecha_presentacion' => $request->fecha_presentacion,
                'fecha_transmision' => $request->fecha_transmision,
                'fecha_vencimiento' => $fechaVencimiento->toDateString(),
                'tipo_transmision_id' => $request->tipo_transmision_id,
                'base_imponible' => $request->base_imponible,
                'valor_declarado' => $request->base_imponible,
                'observaciones' => $request->observaciones,
                'updated_by' => auth()->id(),
            ]);
            Log::info('UPDATE: Trámite actualizado');

            // Actualizar o crear adquirente
            $adquirente = $tramite->adquirentes()->first();
            if (!$adquirente) {
                $adquirente = $tramite->adquirentes()->create([
                    'person_id' => $request->person_id,
                    'porcentaje' => $request->porcentaje,
                ]);
                Log::info('UPDATE: Adquirente creado');
            } else {
                $adquirente->update([
                    'person_id' => $request->person_id,
                    'porcentaje' => $request->porcentaje,
                ]);
                Log::info('UPDATE: Adquirente actualizado');
            }

            // Obtener parentesco basado en categoría de tasa
            $parentesco = Parentesco::getPrimerParentescoPorCategoria((int) $request->categoria_tasa);
            if (!$parentesco) {
                throw new \Exception('No se encontró parentesco para la categoría de tasa seleccionada: ' . $request->categoria_tasa);
            }

            // Actualizar parentesco del adquirente
            $adquirente->update([
                'parentesco_id' => $parentesco->id,
            ]);
            Log::info('UPDATE: Parentesco actualizado');

            // Recalcular
            $calculator = app(IdtgbCalculator::class);
            $departamentoId = $beni->id;

            $adquirentesData = [[
                'person_id' => $request->person_id,
                'parentesco_id' => $parentesco->id,
                'porcentaje' => $request->porcentaje,
            ]];

            $resultados = $calculator->performCalculation(
                $request->base_imponible,
                $departamentoId,
                $request->tipo_transmision_id,
                $request->fecha_presentacion,
                $request->fecha_transmision,
                $fechaVencimiento->toDateString(),
                $adquirentesData,
                [],
                $request->tipo_contribuyente,
                $request->porcentaje
            );
            Log::info('UPDATE: Cálculo completado');

            // Actualizar trámite con resultados
            $tramite->withoutEvents(function () use ($tramite, $resultados) {
                $tramite->update([
                    'total_idtgb' => $resultados['idtgb_base'],
                    'tributo_actualizado' => $resultados['tributo_actualizado'],
                    'recargo_mora' => $resultados['interes'],
                    'multa_idf' => $resultados['multa_idf'],
                    'monto_final' => $resultados['final'],
                    'ufv_aplicada' => $resultados['ufv_pago'],
                    'ufv_vencimiento' => $resultados['ufv_vencimiento'],
                    'dias_mora' => $resultados['dias_mora'],
                    'categoria_tasa' => $resultados['categoria_tasa'],
                ]);
            });
            Log::info('UPDATE: Trámite actualizado con resultados');

            // Actualizar adquirente con tasa calculada
            $tasaModel = $calculator->tasaVigente(
                $departamentoId,
                $parentesco->id,
                $request->tipo_transmision_id,
                Carbon::now()->toDateString()
            );

            $tasaVal = $tasaModel ? $tasaModel->tasa : 0;
            $baseSujeto = $request->base_imponible * ($request->porcentaje / 100);

            $adquirente->update([
                'tasa_aplicada' => $tasaVal,
                'idtgb_proporcional' => round($baseSujeto * ($tasaVal / 100), 2)
            ]);
            Log::info('UPDATE: Adquirente actualizado con tasa');

            DB::commit();
            Log::info('UPDATE: Transacción commit');

            return redirect()->route('admin.tramites.show', $tramite->id)
                ->with(['message' => 'Trámite actualizado exitosamente.', 'alert-type' => 'success']);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('UPDATE: Error - ' . $e->getMessage());
            return back()->withInput()
                ->with(['message' => 'Error al actualizar el trámite: ' . $e->getMessage(), 'alert-type' => 'error']);
        }
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
            if ($person->person_type === 'Jurídica') {
                $name = $person->legal_name ?? 'Sin razón social';
                $doc = 'NIT: ' . ($person->nit ?? 'Sin NIT');
            } else {
                $name = $person->nombre_completo ?? 'Nombre no definido';
                $doc = 'CI: ' . ($person->ci ?? 'Sin CI');
            }
            return [
                'id' => $person->id,
                'text' => $name . ' - ' . $doc,
                'person_type' => $person->person_type,
                'document' => $person->person_type === 'Jurídica' ? ($person->nit ?? 'Sin NIT') : ($person->ci ?? 'Sin CI'),
            ];
        });

        return response()->json(['results' => $formatted]);
    }

    public function ajaxCalculate(Request $request, IdtgbCalculator $calculator)
    {
        $request->validate([
            'base_imponible' => 'required|numeric|min:0',
            'categoria_tasa' => 'required|in:1,10,20',
            'tipo_transmision_id' => 'required|exists:tipos_transmision,id',
            'fecha_transmision' => 'required|date',
            'porcentaje' => 'required|numeric|min:1|max:100',
            'tipo_contribuyente' => 'required|in:Natural,Jurídica',
        ]);

        try {
            // Usar departamento Beni directamente
            $beni = Departamento::select('id', 'nombre', 'codigo')->where('codigo', 'BE')->first();
            $departamentoId = $beni->id;

            // Calcular fecha de vencimiento según tipo de transmisión (Ley 812)
            $fechaTransmision = Carbon::parse($request->fecha_transmision);
            $tipoTransmision = TipoTransmision::find($request->tipo_transmision_id);
            $diasHabilesService = app(DiasHabilesService::class);
            
            if ($tipoTransmision && strtolower($tipoTransmision->nombre) === 'mortis causa') {
                // Sucesiones hereditarias: 90 días calendario
                $fechaVencimiento = $fechaTransmision->copy()->addDays(90);
            } else {
                // Donaciones (Entre vivos): 5 días hábiles (excluyendo sábados, domingos y feriados)
                $fechaVencimiento = $diasHabilesService->calcularVencimiento(
                    $fechaTransmision,
                    5,
                    $departamentoId
                );
            }

            // Obtener parentesco_id a partir de categoria_tasa
            $parentesco = Parentesco::getPrimerParentescoPorCategoria((int) $request->categoria_tasa);
            
            if (!$parentesco) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se encontró parentesco para la categoría de tasa seleccionada: ' . $request->categoria_tasa,
                ], 400);
            }

            $adquirentesData = [[
                'parentesco_id' => $parentesco->id,
                'porcentaje' => $request->porcentaje,
            ]];

            $resultados = $calculator->performCalculation(
                $request->base_imponible,
                $departamentoId,
                $request->tipo_transmision_id,
                Carbon::now()->toDateString(),
                $fechaTransmision->toDateString(),
                $fechaVencimiento->toDateString(),
                $adquirentesData,
                [],
                $request->tipo_contribuyente,
                $request->porcentaje
            );

            return response()->json([
                'success' => true,
                'resultados' => $resultados,
            ]);

        } catch (\Throwable $e) {
            Log::error('Error en cálculo AJAX: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
