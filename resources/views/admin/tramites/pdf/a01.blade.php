<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Formulario A-01 - IDTGB Beni</title>
    {{-- Estilos mejorados para un diseño profesional en una sola página --}}
    <style>
        @page { margin: 20px; }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
        }
        .header {
            text-align: center;
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .header h2 { margin: 0; font-size: 18px; }
        .header h3 { margin: 0; font-size: 14px; font-weight: normal; }
        .header p { margin: 0; font-size: 10px; }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .table th, .table td {
            border: 1px solid #ccc;
            padding: 4px;
            text-align: left;
        }
        .table th { background: #f2f2f2; font-weight: bold; }
        .section-title { font-size: 13px; font-weight: bold; margin-top: 10px; margin-bottom: 5px; border-bottom: 1px solid #eee; padding-bottom: 3px; }
        .footer { margin-top: 20px; font-size: 9px; text-align: center; color: #666; }
        .qr-section { text-align: right; vertical-align: top; }
        .validation-section { vertical-align: top; }
        .total-row td { font-weight: bold; background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <h2>FORMULARIO A-01</h2>
        <h3>Impuesto Departamental a la Transmisión Gratuita de Bienes - IDTGB</h3>
        <p>Departamento de Beni - Bolivia</p>
    </div>

    {{-- Secciones 1 y 3 en columnas --}}
    <table style="width: 100%; border: none; margin-bottom: 10px;">
        <tr style="vertical-align: top;">
            <td style="width: 50%; padding-right: 10px;">
                <div class="section-title">1. Datos del Trámite</div>
                <table class="table">
                    <tr><th>Nro Trámite</th><td>{{ $tramite->nro_tramite }}</td></tr>
                    <tr><th>F. Presentación</th><td>{{ $tramite->fecha_presentacion->format('d/m/Y') }}</td></tr>
                    <tr><th>F. Transmisión</th><td>{{ $tramite->fecha_transmision->format('d/m/Y') }}</td></tr>
                    <tr><th>F. Vencimiento</th><td>{{ $tramite->fecha_vencimiento->format('d/m/Y') }}</td></tr>
                    <tr><th>Tipo</th><td>{{ $tramite->tipoTransmision->nombre }}</td></tr>
                    <tr><th>UFV Venc</th><td>{{ number_format($tramite->ufv_vencimiento ?? 0, 5) }}</td></tr>
                    <tr><th>UFV Pago</th><td>{{ number_format($tramite->ufv_aplicada, 5) }}</td></tr>
                    <tr><th>Días Mora</th><td>{{ $tramite->dias_mora ?? 0 }}</td></tr>
                </table>
            </td>
            <td style="width: 50%; padding-left: 10px;">
                <div class="section-title">2. Cálculo IDTGB (Ley 812)</div>
                <table class="table">
                    <tr><th>Base Imponible</th><td>Bs {{ number_format($tramite->base_imponible, 2) }}</td></tr>
                    <tr><th>Categoría Tasa</th><td>{{ $tramite->categoria_tasa == 1 ? 'Línea Directa (1%)' : ($tramite->categoria_tasa == 10 ? 'Colateral (10%)' : 'Otros (20%)') }}</td></tr>
                    <tr><th>Tributo Omitido (Base)</th><td>Bs {{ number_format($tramite->total_idtgb, 2) }}</td></tr>
                    <tr><th>Tributo Actualizado (UFV)</th><td>Bs {{ number_format($tramite->tributo_actualizado ?? $tramite->total_idtgb, 2) }}</td></tr>
                    <tr><th>Intereses (Mora)</th><td>Bs {{ number_format($tramite->recargo_mora, 2) }}</td></tr>
                    <tr><th>Multa IDF</th><td>Bs {{ number_format($tramite->multa_idf ?? 0, 2) }}</td></tr>
                    <tr class="total-row"><td>Monto Final</td><td>Bs {{ number_format($tramite->monto_final, 2) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title">3. Adquirentes</div>
    <table class="table">
        <thead>
            <tr><th>Nombre</th><th>%</th><th>Tasa</th><th>IDTGB Proporcional</th></tr>
        </thead>
        <tbody>
            @foreach($tramite->adquirentes as $a)
            <tr>
                <td>{{ $a->person->fullName }}</td>
                <td>{{ $a->porcentaje }} %</td>
                <td>{{ $a->tasa_aplicada }} %</td>
                <td>Bs {{ number_format($a->idtgb_proporcional, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        <table style="width: 100%; border-collapse: collapse; border: none;">
            <tbody>
                <tr>
                    <td class="validation-section" style="width: 70%; padding: 0;">
                        <div class="section-title">4. Validación</div>
                        <p style="font-size: 9px; margin: 0; word-break: break-all;">
                            <strong>Hash:</strong> {{ $hash }}
                        </p>
                        <p style="font-size: 10px; margin-top: 5px;">
                            Verifique la autenticidad de este documento en:<br>
                            <a href="{{ route('tramite.validar', ['hash' => $hash]) }}">{{ route('tramite.validar', ['hash' => $hash]) }}</a>
                        </p>
                    </td>
                    <td class="qr-section" style="width: 30%; padding: 0;">
                        <img src="data:image/svg+xml;base64,{{ $qr }}" alt="QR Code" style="width: 90px; height: 90px;">
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div style="margin-top:30px; text-align:center;">
        <p style="margin-bottom: 0;">_________________________</p>
        <small>Firma y Sello - Funcionario IDTGB Beni</small>
    </div>

    <div class="footer">
        <p>Formulario generado el {{ now()->format('d/m/Y H:i') }}</p>
        <p>Usuario: {{ auth()->user()->name }}</p>
    </div>
</body>
</html>
