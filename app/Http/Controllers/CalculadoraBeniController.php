<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Parentesco;
use App\Models\TipoTransmision;
use App\Services\IdtgbCalculator;
use App\Services\DiasHabilesService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CalculadoraBeniController extends Controller
{
    const CODIGO_BENI = 'BE';

    public function formulario()
    {
        $categorias = Cache::remember('categorias.tasa.select', 3600, function () {
            return Parentesco::agruparPorCategorias();
        });

        $tipos_transmision = Cache::remember('tipos_transmision.all', 3600, function () {
            return TipoTransmision::all();
        });

        return view('calculadora_beni_interactivo', [
            'categorias' => $categorias,
            'tipos_transmision' => $tipos_transmision,
            'nro_tramite' => null,
        ]);
    }

    public function calcular(Request $request, IdtgbCalculator $calculator)
    {
        $request->validate([
            'nombre_sujeto' => 'nullable|string|max:150',
            'ci_sujeto' => 'nullable|string|max:20',
            'tipo_contribuyente' => 'required|in:Natural,Jurídica',
            'categoria_tasa' => 'required|integer|in:1,10,20',
            'fecha_transmision' => 'required|date|before_or_equal:today',
            'base_imponible' => 'required|numeric|min:0.01',
            'tipo_transmision' => 'required|string',
            'participacion' => 'required|numeric|min:1|max:100',
        ], [
            'fecha_transmision.before_or_equal' => 'La fecha de transmisión no puede ser futura.',
            'categoria_tasa.required' => 'Debe seleccionar una categoría de parentesco.',
            'categoria_tasa.in' => 'La categoría de tasa debe ser 1, 10 o 20.',
        ]);

        Log::info('Calculadora Beni: Nuevo cálculo solicitado', [
            'base' => $request->base_imponible,
            'tipo' => $request->tipo_transmision,
            'categoria' => $request->categoria_tasa,
            'ip' => $request->ip(),
        ]);

        $beniId = Departamento::where('codigo', self::CODIGO_BENI)->firstOrFail()->id;
        $tipoTransmisionId = TipoTransmision::where('nombre', $request->tipo_transmision)->first()?->id ?? 1;

        $parentescoId = $this->getParentescoIdFromCategoria((int) $request->categoria_tasa);

        $fecha_transmision = Carbon::parse($request->fecha_transmision);
        
        // Calcular fecha de vencimiento según tipo de transmisión (Ley 812)
        $tipoTransmision = TipoTransmision::find($tipoTransmisionId);
        $diasHabilesService = app(DiasHabilesService::class);
        
        if ($tipoTransmision && strtolower($tipoTransmision->nombre) === 'mortis causa') {
            // Sucesiones hereditarias: 90 días calendario
            $fecha_vencimiento = $fecha_transmision->copy()->addDays(90);
        } else {
            // Donaciones (Entre vivos): 5 días hábiles (excluyendo sábados, domingos y feriados)
            $fecha_vencimiento = $diasHabilesService->calcularVencimiento(
                $fecha_transmision,
                5,
                $beniId
            );
        }

        $calculo = $calculator->calculateEstimate(
            (float) $request->base_imponible,
            $beniId,
            $parentescoId,
            $tipoTransmisionId,
            $fecha_transmision->toDateString(),
            Carbon::now()->toDateString(),
            $fecha_vencimiento->toDateString(),
            $request->tipo_contribuyente,
            (float) $request->participacion
        );

        $categoriaLabel = match((int) $request->categoria_tasa) {
            1 => 'Línea Directa',
            10 => 'Línea Colateral',
            20 => 'Otros',
            default => 'No especificado',
        };

        return response()->json(array_merge($calculo, [
            'nombre_sujeto' => strtoupper($request->nombre_sujeto ?? 'CONSULTA REFERENCIAL'),
            'ci_sujeto' => $request->ci_sujeto ?? 'S/N',
            'tipo_contribuyente' => $request->tipo_contribuyente,
            'categoria_tasa' => (int) $request->categoria_tasa,
            'categoria_label' => $categoriaLabel,
        ]));
    }

    public function descargarPdf(Request $request, IdtgbCalculator $calculator)
    {
        $beniId = Departamento::where('codigo', self::CODIGO_BENI)->firstOrFail()->id;
        $tipoTransmisionId = TipoTransmision::where('nombre', $request->tipo_transmision)->first()?->id ?? 1;

        $parentescoId = $this->getParentescoIdFromCategoria((int) ($request->categoria_tasa ?? 1));

        $fecha_transmision = Carbon::parse($request->fecha_transmision);
        
        // Calcular fecha de vencimiento según tipo de transmisión (Ley 812)
        $tipoTransmision = TipoTransmision::find($tipoTransmisionId);
        $diasHabilesService = app(DiasHabilesService::class);
        
        if ($tipoTransmision && strtolower($tipoTransmision->nombre) === 'mortis causa') {
            // Sucesiones hereditarias: 90 días calendario
            $fecha_vencimiento = $fecha_transmision->copy()->addDays(90);
        } else {
            // Donaciones (Entre vivos): 5 días hábiles (excluyendo sábados, domingos y feriados)
            $fecha_vencimiento = $diasHabilesService->calcularVencimiento(
                $fecha_transmision,
                5,
                $beniId
            );
        }

        $calculo = $calculator->calculateEstimate(
            (float) $request->base_imponible,
            $beniId,
            $parentescoId,
            $tipoTransmisionId,
            $request->fecha_transmision,
            Carbon::now()->toDateString(),
            $fecha_vencimiento->toDateString(),
            $request->tipo_contribuyente,
            (float) $request->participacion
        );

        $categoriaLabel = match((int) ($request->categoria_tasa ?? 1)) {
            1 => 'Línea Directa',
            10 => 'Línea Colateral',
            20 => 'Otros',
            default => 'No especificado',
        };

        $dataReporte = array_merge($calculo, [
            'nombre_sujeto' => strtoupper($request->nombre_sujeto ?? 'CONSULTA REFERENCIAL'),
            'ci_sujeto' => $request->ci_sujeto ?? 'S/N',
            'tipo_contribuyente' => $request->tipo_contribuyente,
            'telefono' => $request->telefono ?? '',
            'parentesco' => $categoriaLabel,
            'tipo_transmision_nombre' => $request->tipo_transmision,
        ]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.calculo_estimado_beni', $dataReporte);

        return $pdf->download('Preliquidacion_IDTGB_Beni_'.$calculo['nro_tramite'].'.pdf');
    }

    private function getParentescoIdFromCategoria(int $categoria): int
    {
        $parentesco = Parentesco::getPrimerParentescoPorCategoria($categoria);
        
        if (!$parentesco) {
            $defaultCategories = [
                1 => Parentesco::where('nombre', 'Cónyuge o Conviviente')->first()?->id,
                10 => Parentesco::where('nombre', 'Hermano/a')->first()?->id,
                20 => Parentesco::where('nombre', 'Sin parentesco')->first()?->id,
            ];
            return $defaultCategories[$categoria] ?? 1;
        }

        return $parentesco->id;
    }
}
