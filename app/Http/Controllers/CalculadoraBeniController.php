<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Models\Inmueble;
    use App\Models\Parentesco;
    use App\Models\Tasa;
    use App\Models\UFV;
    use App\Models\Person;
    use Carbon\Carbon;
    use Barryvdh\DomPDF\Facade\Pdf;
    use SimpleSoftwareIO\QrCode\Facades\QrCode;

    class CalculadoraBeniController extends Controller
    {

        // public function show($hash)
        // {
        //     return view('qr.verificar', compact('hash'));
        // }
        /* ------------------------------------------------------------------
        |  PASO 1: Mostrar formulario (estilo Portal Ciudadano)
        * ------------------------------------------------------------------ */
        public function formulario()
        {
            // Solo inmuebles del departamento Beni
            $inmuebles = Inmueble::whereHas('municipio.provincia.departamento', function ($q) {
                $q->where('nombre', 'Beni');
            })->get();

            $parentescos = Parentesco::all();
            $personas    = Person::all();

            return view('calculadora_beni_interactivo', compact('inmuebles', 'parentescos', 'personas'));
        }

        /* ------------------------------------------------------------------
        |  PASO 2: Calcular y devolver JSON o PDF
        * ------------------------------------------------------------------ */
        public function calcular(Request $request)
        {
            $request->validate([
                'tipo_contribuyente' => 'required|in:Natural,Jurídica', // <-- nuevo
                'inmueble_id'        => 'required|exists:inmuebles,id',
                'parentesco_id'      => 'required|exists:parentescos,id',
                'fecha_transmision'  => 'required|date',
                'base_imponible'     => 'required|numeric|min:0',
                'tipo_transmision'   => 'required|string|in:Entre vivos,Testamento',
            ]);

            // 1. Objetos que sí usamos
            $inmueble   = Inmueble::findOrFail($request->inmueble_id);
            $parentesco = Parentesco::findOrFail($request->parentesco_id);

            // 2. Fechas, UFV, tasa, cálculos (todo igual)
            $fecha_transmision = Carbon::parse($request->fecha_transmision);
            $fecha_vencimiento = $fecha_transmision->copy()->addDays(30);
            $dias_mora         = max(0, Carbon::now()->diffInDays($fecha_vencimiento, false));
            $ufv = UFV::where('fecha', '<=', $request->fecha_transmision)
                    ->orderBy('fecha', 'desc')
                    ->first()->valor ?? 1;
            $tasa = Tasa::where('parentesco_id', $request->parentesco_id)
                        ->whereHas('departamento', fn($q) => $q->where('nombre', 'Beni'))
                        ->where('vigente_desde', '<=', $request->fecha_transmision)
                        ->where(fn($q) => $q->whereNull('vigente_hasta')
                                            ->orWhere('vigente_hasta', '>=', $request->fecha_transmision))
                        ->orderBy('vigente_desde', 'desc')
                        ->first()->tasa ?? 0;

            $base_imponible  = $request->base_imponible;
            $tributo_omitido = $base_imponible * ($tasa / 100);
            $descuento       = $tributo_omitido * 0.15;
            $monto_final     = $tributo_omitido - $descuento;

            // $hash = substr(hash('sha256', $monto_final . now()->timestamp), 0, 8); // 8 caracteres
            // $urlPublica = route('qr.verificar', ['hash' => $hash]); // https://tudominio.com/verificar/a1b2c3d4

            // $qrCodePng = QrCode::size(180)
            //     ->errorCorrection('H')
            //     ->generate($urlPublica);

            // CÓDIGO CORREGIDO PARA USAR GD POR DEFECTO:
            $qrCodePng = QrCode::size(180)
                ->errorCorrection('H')
                ->generate('IDTGB-Beni|Bs:' . round($monto_final, 2) . '|Fecha:' . now()->format('Y-m-d'));

            // Codificar la imagen PNG a Base64 para DOMPDF
            $qrDataUrl = 'data:image/png;base64,' . base64_encode($qrCodePng);


            // 3. Array para vista o PDF
            $resultado = [
                'tipo_contribuyente' => $request->tipo_contribuyente, // <-- nuevo
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
                'descuento'          => round($descuento, 2),
                'monto_final'        => round($monto_final, 2),
                'cuenta_banco'       => '1000000000000',
                'qrDataUrl'         => $qrDataUrl,
            ];

            if ($request->has('download_pdf')) {
                $pdf = Pdf::loadView('pdf.form_a01_beni_interactivo', $resultado);
                return $pdf->download('IDTGB_Beni_Form_A01_Calculo.pdf');
            }

            return response()->json($resultado);
        }
    }
