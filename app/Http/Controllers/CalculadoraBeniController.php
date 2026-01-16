<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Parentesco;
use App\Models\TipoTransmision;
use App\Services\IdtgbCalculator;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CalculadoraBeniController extends Controller
{
    public function formulario()
    {
        $parentescos = Parentesco::all();
        $tipos_transmision = TipoTransmision::all();

        return view('calculadora_beni_interactivo', [
                'parentescos' => $parentescos,
                'tipos_transmision' => $tipos_transmision,
                'nro_tramite' => null
            ]);
    }

    public function calcular(Request $request, IdtgbCalculator $calculator)
    {
        $request->validate([
            'nombre_sujeto'      => 'nullable|string|max:150',
            'ci_sujeto'          => 'nullable|string|max:20',
            'tipo_contribuyente' => 'required|in:Natural,Jurídica',
            'parentesco_id'      => 'required|exists:parentescos,id',
            'fecha_transmision'  => 'required|date',
            'base_imponible'     => 'required|numeric|min:0.01',
            'tipo_transmision'   => 'required|string',
            'participacion'      => 'required|numeric|min:1|max:100',
        ]);

        $beniId = Departamento::where('codigo', 'BE')->firstOrFail()->id;
        $tipoTransmisionId = TipoTransmision::where('nombre', $request->tipo_transmision)->first()?->id ?? 1;

        $fecha_transmision = Carbon::parse($request->fecha_transmision);
        $fecha_vencimiento = $fecha_transmision->copy()->addDays(90);

        // Ejecutar el cálculo con el nuevo factor de participación
        $calculo = $calculator->calculateEstimate(
            (float)$request->base_imponible,
            $beniId,
            (int)$request->parentesco_id,
            $tipoTransmisionId,
            $fecha_transmision->toDateString(),
            Carbon::now()->toDateString(),
            $fecha_vencimiento->toDateString(),
            $request->tipo_contribuyente,
            (float)$request->participacion
        );

        // Añadimos los datos del sujeto al array de respuesta
        return response()->json(array_merge($calculo, [
            'nombre_sujeto' => strtoupper($request->nombre_sujeto ?? 'CONSULTA REFERENCIAL'),
            'ci_sujeto'     => $request->ci_sujeto ?? 'S/N',
        ]));
    }

    public function descargarPdf(Request $request, IdtgbCalculator $calculator)
    {
        // El PDF requiere los mismos datos que el cálculo
        $beniId = Departamento::where('codigo', 'BE')->firstOrFail()->id;
        $tipoTransmisionId = TipoTransmision::where('nombre', $request->tipo_transmision)->first()?->id ?? 1;

        $fecha_transmision = Carbon::parse($request->fecha_transmision);
        $fecha_vencimiento = $fecha_transmision->copy()->addDays(90);

        $calculo = $calculator->calculateEstimate(
            (float)$request->base_imponible,
            $beniId,
            (int)$request->parentesco_id,
            $tipoTransmisionId,
            $request->fecha_transmision,
            Carbon::now()->toDateString(),
            $fecha_vencimiento->toDateString(),
            $request->tipo_contribuyente,
            (float)$request->participacion
        );

        // Datos adicionales para el reporte formal
        $dataReporte = array_merge($calculo, [
            'nombre_sujeto' => strtoupper($request->nombre_sujeto ?? 'CONSULTA REFERENCIAL'),
            'ci_sujeto'     => $request->ci_sujeto ?? 'S/N',
            'tipo_contribuyente' => $request->tipo_contribuyente,
            'telefono'      => $request->telefono ?? '',
            'parentesco'    => Parentesco::find($request->parentesco_id)->nombre,
            'tipo_transmision_nombre' => $request->tipo_transmision
        ]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.calculo_estimado_beni', $dataReporte);

        return $pdf->download('Preliquidacion_IDTGB_Beni_' . $calculo['nro_tramite'] . '.pdf');
    }
}
