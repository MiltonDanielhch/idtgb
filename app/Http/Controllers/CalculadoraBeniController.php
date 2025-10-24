<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use Illuminate\Http\Request;
use App\Models\Parentesco;
use App\Models\TipoTransmision;
use App\Models\UFV;
use App\Services\IdtgbCalculator;
use Carbon\Carbon;

class CalculadoraBeniController extends Controller
{
    /**
     * PASO 1: Mostrar formulario de cálculo rápido (solo orientación)
     */
    public function formulario()
    {
        // ✅ Solo cargar listas básicas (sin JOINs complejos)
        $parentescos = Parentesco::all();
        $tipos_transmision = TipoTransmision::all();

        return view('calculadora_beni_interactivo', compact('parentescos', 'tipos_transmision'));
    }

    /**
     * PASO 2: Calcular estimación del IDTGB usando el servicio centralizado
     */
    public function calcular(Request $request, IdtgbCalculator $calculator)
    {
        $request->validate([
            'tipo_contribuyente' => 'required|in:Natural,Jurídica',
            'parentesco_id'      => 'required|exists:parentescos,id',
            'fecha_transmision'  => 'required|date',
            'base_imponible'     => 'required|numeric|min:0.01',
            'tipo_transmision'   => 'required|string|in:Herencia,Donación,Legado',
        ]);

        // 1. Obtener IDs y datos necesarios
        $beniId = Departamento::where('codigo', 'BE')->firstOrFail()->id;
        $tipoTransmisionId = TipoTransmision::where('nombre', $request->tipo_transmision)->firstOrFail()->id;
        $fecha_transmision = Carbon::parse($request->fecha_transmision);
        $fecha_vencimiento = $fecha_transmision->copy()->addDays(30);

        // 2. Llamar al servicio para el cálculo
        $calculo = $calculator->calculateEstimate(
            (float)$request->base_imponible,
            $beniId,
            (int)$request->parentesco_id,
            $tipoTransmisionId,
            $fecha_transmision->toDateString(), // ✅ Pasar fecha_transmision
            $fecha_transmision->toDateString(),
            $fecha_vencimiento->toDateString()
        );

        // 3. Preparar el resultado para la vista
        $parentesco = Parentesco::find($request->parentesco_id);
        $ufv = UFV::whereDate('fecha', '<=', $fecha_transmision)
                   ->orderBy('fecha', 'desc')
                   ->first()?->valor ?? 1.00000;

        $resultado = [
            'tipo_contribuyente' => $request->tipo_contribuyente,
            'parentesco'         => $parentesco,
            'tipo_transmision'   => $request->tipo_transmision,
            'fecha_transmision'  => $fecha_transmision->format('d/m/Y'),
            'fecha_vencimiento'  => $fecha_vencimiento->format('d/m/Y'),
            'dias_mora'          => $calculo['dias_mora'],
            'base_imponible'     => round($calculo['base'], 2),
            'ufv'                => $ufv,
            'tasa'               => $calculo['detalles_tasas'][0]['tasa_aplicada'],
            'tributo_omitido'    => round($calculo['tasas'], 2),
            'monto_final'        => round($calculo['final'], 2),
            'cuenta_banco'       => '1000000000000',
        ];

        return response()->json($resultado);
    }

    /**
     * Generar PDF del cálculo estimado (NO es formulario A-01 oficial)
     */
    public function descargarPdf(Request $request, IdtgbCalculator $calculator)
    {
        $request->validate([
            'tipo_contribuyente' => 'required|in:Natural,Jurídica',
            'parentesco_id'      => 'required|exists:parentescos,id',
            'fecha_transmision'  => 'required|date',
            'base_imponible'     => 'required|numeric|min:0.01',
            'tipo_transmision'   => 'required|string|in:Herencia,Donación,Legado',
        ]);

        $beniId = Departamento::where('codigo', 'BE')->firstOrFail()->id;
        $tipoTransmisionId = TipoTransmision::where('nombre', $request->tipo_transmision)->firstOrFail()->id;
        $fecha_transmision = Carbon::parse($request->fecha_transmision);
        $fecha_vencimiento = $fecha_transmision->copy()->addDays(30);

        $calculo = $calculator->calculateEstimate(
            (float)$request->base_imponible,
            $beniId,
            (int)$request->parentesco_id,
            $tipoTransmisionId,
            $fecha_transmision->toDateString(), // ✅ Pasar fecha_transmision
            $fecha_transmision->toDateString(),
            $fecha_vencimiento->toDateString()
        );

        $parentesco = Parentesco::find($request->parentesco_id);

        $data = [
            'tipo_contribuyente' => $request->tipo_contribuyente,
            'parentesco'         => $parentesco,
            'tipo_transmision'   => $request->tipo_transmision,
            'fecha_transmision'  => $fecha_transmision->format('d/m/Y'),
            'fecha_vencimiento'  => $fecha_vencimiento->format('d/m/Y'),
            'base_imponible'     => round($calculo['base'], 2),
            'tasa'               => $calculo['detalles_tasas'][0]['tasa_aplicada'],
            'tributo_omitido'    => round($calculo['tasas'], 2),
            'monto_final'        => round($calculo['final'], 2),
            'es_calculo_estimado' => true,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.calculo_estimado_beni', $data);
        return $pdf->download('IDTGB_Beni_Calculo_Estimado.pdf');
    }
}
