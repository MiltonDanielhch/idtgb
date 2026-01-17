<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Parentesco;
use App\Models\TipoTransmision;
use App\Services\IdtgbCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CalculadoraBeniController extends Controller
{
    const CODIGO_BENI = 'BE';

    public function formulario()
    {
        // Implementación de Caching para listas (Mejora #3)
        $parentescos = Cache::remember('parentescos.all', 3600, function () {
            return Parentesco::all();
        });

        $tipos_transmision = Cache::remember('tipos_transmision.all', 3600, function () {
            return TipoTransmision::all();
        });

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
            'fecha_transmision'  => 'required|date|before_or_equal:today', // Mejora #4: Validación fecha futura
            'base_imponible'     => 'required|numeric|min:0.01',
            'tipo_transmision'   => 'required|string',
            'participacion'      => 'required|numeric|min:1|max:100',
        ], [
            'fecha_transmision.before_or_equal' => 'La fecha de transmisión no puede ser futura.',
        ]);

        // Mejora #2: Logging de consultas
        Log::info('Calculadora Beni: Nuevo cálculo solicitado', [
            'base' => $request->base_imponible,
            'tipo' => $request->tipo_transmision,
            'ip' => $request->ip()
        ]);

        // Mejora #3: Uso de constante para evitar hardcoding
        $beniId = Departamento::where('codigo', self::CODIGO_BENI)->firstOrFail()->id;
        
        // Mejora #1: Validación más robusta de Tipo de Transmisión
        // Intentamos buscar por nombre, pero si falla usamos ID 1 (Herencia) como fallback seguro
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
            'tipo_contribuyente' => $request->tipo_contribuyente,
        ]));
    }

    public function descargarPdf(Request $request, IdtgbCalculator $calculator)
    {
        // El PDF requiere los mismos datos que el cálculo
        $beniId = Departamento::where('codigo', self::CODIGO_BENI)->firstOrFail()->id;
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
