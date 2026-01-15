<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Parentesco;
use App\Models\TipoTransmision;
use App\Models\Ufv;
use App\Services\IdtgbCalculator;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CalculadoraBeniController extends Controller
{
    public function formulario()
    {
        $parentescos = Parentesco::all();
        $tipos_transmision = TipoTransmision::all();

        return view('calculadora_beni_interactivo', compact('parentescos', 'tipos_transmision'));
    }

    public function calcular(Request $request, IdtgbCalculator $calculator)
    {
        $request->validate([
            'tipo_contribuyente' => 'required|in:Natural,Jurídica',
            'parentesco_id'      => 'required|exists:parentescos,id',
            'fecha_transmision'  => 'required|date',
            'base_imponible'     => 'required|numeric|min:0.01',
            'tipo_transmision'   => 'required|string',
        ]);

        // 1. Configuración de parámetros para el Beni
        $beniId = Departamento::where('codigo', 'BE')->firstOrFail()->id;
        $tipoTransmisionId = TipoTransmision::where('nombre', $request->tipo_transmision)->first()
            ?->id ?? TipoTransmision::first()->id;

        $fecha_transmision = Carbon::parse($request->fecha_transmision);

        // CORRECCIÓN LEGAL: El plazo para IDTGB (sucesiones/donaciones) es de 90 días
        $fecha_vencimiento = $fecha_transmision->copy()->addDays(90);

        // 2. Ejecutar el cálculo mediante el Servicio
        $calculo = $calculator->calculateEstimate(
            (float)$request->base_imponible,
            $beniId,
            (int)$request->parentesco_id,
            $tipoTransmisionId,
            $fecha_transmision->toDateString(),
            Carbon::now()->toDateString(), // Fecha de hoy (Presentación/Pago)
            $fecha_vencimiento->toDateString(),
            $request->tipo_contribuyente
        );

        // 3. Preparar respuesta para la vista (Boleta Ley 812)
        return response()->json([
            'tipo_contribuyente' => $request->tipo_contribuyente,
            'fecha_transmision'  => $calculo['fecha_transmision'],
            'fecha_vencimiento'  => $calculo['fecha_vencimiento'],
            'base_imponible'     => $calculo['base'],
            'tasa'               => $calculo['detalles_tasas'][0]['tasa_aplicada'] ?? 0,
            'idtgb_base'         => $calculo['idtgb_base'],      // S900 (Tributo Omitido)
            'mantenimiento_valor'=> $calculo['mantenimiento_valor'], // S920
            'interes'            => $calculo['interes'],            // S930
            'multa_idf'          => $calculo['multa_idf'],          // S900 (Multa)
            'dias_mora'          => $calculo['dias_mora'],
            'ufv_aplicada'       => $calculo['ufv_pago'],
            'final'              => $calculo['final']               // Total Deuda
        ]);
    }

    public function descargarPdf(Request $request, IdtgbCalculator $calculator)
    {
        $request->validate([
            'tipo_contribuyente' => 'required|in:Natural,Jurídica',
            'parentesco_id'      => 'required|exists:parentescos,id',
            'fecha_transmision'  => 'required|date',
            'base_imponible'     => 'required|numeric|min:0.01',
            'tipo_transmision'   => 'required|string',
        ]);

        // 1. DEFINICIÓN DE VARIABLES (Esto elimina el error P1008)
        $beniId = \App\Models\Departamento::where('codigo', 'BE')->firstOrFail()->id;

        $tipoTransmisionId = \App\Models\TipoTransmision::where('nombre', $request->tipo_transmision)->first()
            ?->id ?? \App\Models\TipoTransmision::first()->id;

        $fecha_transmision = \Carbon\Carbon::parse($request->fecha_transmision);

        // CORRECCIÓN LEGAL: 90 días de plazo (Ley 812)
        $fecha_vencimiento = $fecha_transmision->copy()->addDays(90);

        // 2. Ejecutar el cálculo
        $calculo = $calculator->calculateEstimate(
            (float)$request->base_imponible,
            $beniId,
            (int)$request->parentesco_id,
            $tipoTransmisionId,
            $fecha_transmision->toDateString(),
            \Carbon\Carbon::now()->toDateString(), // Fecha de Pago (hoy)
            $fecha_vencimiento->toDateString(),
            $request->tipo_contribuyente
        );

        // 3. Generar el PDF
        // Pasamos el array $calculo que contiene idtgb_base, interes, multa_idf, etc.
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.calculo_estimado_beni', $calculo);

        return $pdf->download('IDTGB_Beni_Calculo_Estimado.pdf');
    }
}
