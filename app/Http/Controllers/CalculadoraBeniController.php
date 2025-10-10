<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use Illuminate\Http\Request;
use App\Models\Inmueble;
use App\Models\Parentesco;
use App\Models\Tasa;
use App\Models\UFV;
use App\Models\Person;
use Carbon\Carbon;

class CalculadoraBeniController extends Controller
{
    /**
     * PASO 1: Mostrar formulario de cálculo rápido (solo orientación)
     */
    public function formulario()
    {
        // Obtener ID del departamento Beni
        $beniId = Departamento::where('codigo', 'BE')->firstOrFail()->id;

        // Cargar parentescos con su tasa VIGENTE del Beni
        $parentescos = Parentesco::leftJoin('tasas', function($join) use ($beniId) {
                $join->on('parentescos.id', '=', 'tasas.parentesco_id')
                    ->where('tasas.departamento_id', '=', $beniId)
                    ->where('tasas.vigente_desde', '<=', now())
                    ->where(function($q) {
                        $q->whereNull('tasas.vigente_hasta')
                        ->orWhere('tasas.vigente_hasta', '>=', now());
                    });
            })
            ->select('parentescos.*', 'tasas.tasa as tasa_vigente')
            ->get();

        $inmuebles = Inmueble::whereHas('municipio.provincia.departamento', function ($q) {
            $q->where('codigo', 'BE');
        })->get();

        $personas = Person::all();

        return view('calculadora_beni_interactivo', compact('inmuebles', 'parentescos', 'personas'));
    }

    /**
     * PASO 2: Calcular estimación del IDTGB (sin descuentos ni QR)
     */
    public function calcular(Request $request)
    {
        $request->validate([
            'tipo_contribuyente' => 'required|in:Natural,Jurídica',
            'inmueble_id'        => 'required|exists:inmuebles,id',
            'parentesco_id'      => 'required|exists:parentescos,id',
            'fecha_transmision'  => 'required|date',
            'base_imponible'     => 'required|numeric|min:0.01',
            'tipo_transmision'   => 'required|string|in:Entre vivos,Testamento',
        ]);

        // Obtener datos
        $inmueble   = Inmueble::findOrFail($request->inmueble_id);
        $parentesco = Parentesco::findOrFail($request->parentesco_id);
        $fecha_transmision = Carbon::parse($request->fecha_transmision);
        $fecha_vencimiento = $fecha_transmision->copy()->addDays(30);
        $dias_mora = max(0, Carbon::now()->diffInDays($fecha_vencimiento, false));

        // Obtener UFV vigente
        $ufv = UFV::whereDate('fecha', '<=', $fecha_transmision)
                   ->orderBy('fecha', 'desc')
                   ->first()?->valor ?? 1.00000;

        // Obtener tasa del Beni (vigente en la fecha de transmisión)
        $tasa = Tasa::where('parentesco_id', $request->parentesco_id)
                    ->whereHas('departamento', fn($q) => $q->where('codigo', 'BE'))
                    ->whereDate('vigente_desde', '<=', $fecha_transmision)
                    ->where(function($q) use ($fecha_transmision) {
                        $q->whereNull('vigente_hasta')
                          ->orWhereDate('vigente_hasta', '>=', $fecha_transmision);
                    })
                    ->orderBy('vigente_desde', 'desc')
                    ->first()?->tasa ?? 0.00;

        // 🔥 CÁLCULO REAL DEL BENI: SIN DESCUENTO DEL 15%
        $base_imponible  = $request->base_imponible;
        $tributo_omitido = $base_imponible * ($tasa / 100);
        $monto_final     = $tributo_omitido; // ✅ Sin descuentos

        // Preparar resultado
        $resultado = [
            'tipo_contribuyente' => $request->tipo_contribuyente,
            'inmueble'           => $inmueble,
            'parentesco'         => $parentesco,
            'tipo_transmision'   => $request->tipo_transmision,
            'fecha_transmision'  => $fecha_transmision->format('d/m/Y'),
            'fecha_vencimiento'  => $fecha_vencimiento->format('d/m/Y'),
            'dias_mora'          => $dias_mora,
            'base_imponible'     => round($base_imponible, 2),
            'ufv'                => $ufv,
            'tasa'               => $tasa,
            'tributo_omitido'    => round($tributo_omitido, 2),
            'monto_final'        => round($monto_final, 2),
            'cuenta_banco'       => '1000000000000', // Solo para referencia
        ];

        // ❌ NO se genera PDF ni QR en la calculadora rápida
        return response()->json($resultado);
    }

    /**
     * Generar PDF del cálculo estimado (NO es formulario A-01 oficial)
     */
    public function descargarPdf(Request $request)
    {
        $request->validate([
            'tipo_contribuyente' => 'required|in:Natural,Jurídica',
            'inmueble_id'        => 'required|exists:inmuebles,id',
            'parentesco_id'      => 'required|exists:parentescos,id',
            'fecha_transmision'  => 'required|date',
            'base_imponible'     => 'required|numeric|min:0.01',
            'tipo_transmision'   => 'required|string|in:Entre vivos,Testamento',
        ]);

        // Reutilizar lógica de cálculo
        $inmueble = Inmueble::findOrFail($request->inmueble_id);
        $parentesco = Parentesco::findOrFail($request->parentesco_id);
        $fecha_transmision = Carbon::parse($request->fecha_transmision);
        $fecha_vencimiento = $fecha_transmision->copy()->addDays(30);
        $ufv = UFV::whereDate('fecha', '<=', $fecha_transmision)->orderBy('fecha', 'desc')->first()?->valor ?? 1.00000;
        $tasa = Tasa::where('parentesco_id', $request->parentesco_id)
                    ->whereHas('departamento', fn($q) => $q->where('codigo', 'BE'))
                    ->whereDate('vigente_desde', '<=', $fecha_transmision)
                    ->where(fn($q) => $q->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', $fecha_transmision))
                    ->orderBy('vigente_desde', 'desc')
                    ->first()?->tasa ?? 0.00;

        $base_imponible = $request->base_imponible;
        $tributo_omitido = $base_imponible * ($tasa / 100);
        $monto_final = $tributo_omitido;

        $data = [
            'tipo_contribuyente' => $request->tipo_contribuyente,
            'inmueble' => $inmueble,
            'parentesco' => $parentesco,
            'tipo_transmision' => $request->tipo_transmision,
            'fecha_transmision' => $fecha_transmision->format('d/m/Y'),
            'fecha_vencimiento' => $fecha_vencimiento->format('d/m/Y'),
            'base_imponible' => round($base_imponible, 2),
            'tasa' => $tasa,
            'tributo_omitido' => round($tributo_omitido, 2),
            'monto_final' => round($monto_final, 2),
            'es_calculo_estimado' => true, // Para la vista PDF
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.calculo_estimado_beni', $data);
        return $pdf->download('IDTGB_Beni_Calculo_Estimado.pdf');
    }
}
