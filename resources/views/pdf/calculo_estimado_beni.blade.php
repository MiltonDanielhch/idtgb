<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007A33;
            padding-bottom: 5px;
        }
        .header h2 { color: #007A33; margin: 0; font-size: 16px; }
        .disclaimer {
            background-color: #fff3cd;
            padding: 10px;
            border: 1px solid #ffeaa7;
            color: #856404;
            font-size: 9px;
            text-align: center;
            margin: 10px 0;
            border-radius: 4px;
        }
        .section-title {
            background-color: #f0f8f0;
            padding: 5px;
            font-weight: bold;
            border-left: 4px solid #007A33;
            margin-top: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        th, td {
            border: 1px solid #eee;
            padding: 6px;
            text-align: left;
        }
        th { width: 40%; background-color: #fafafa; }
        .boleta-table { border: 1px solid #007A33; }
        .boleta-table td { border: none; padding: 4px 10px; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .total-box {
            margin-top: 20px;
            text-align: right;
            font-size: 14px;
            color: #007A33;
            border-top: 2px solid #007A33;
            padding-top: 10px;
        }
        .footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            font-size: 8px;
            text-align: center;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>GOBIERNO AUTÓNOMO DEPARTAMENTAL DEL BENI</h2>
        <p style="margin:2px">Dirección de Recaudaciones y Políticas Tributarias</p>
        <p><strong>REPORTE DE SIMULACIÓN IDTGB - LEY 1097 / LEY 812</strong></p>
    </div>

    <div class="disclaimer">
        ⚠️ ESTE DOCUMENTO ES UNA SIMULACIÓN ESTIMADA Y NO CONSTITUYE UN TÍTULO VALOR NI COMPROBANTE DE PAGO OFICIAL.<br>
        EL CÁLCULO ESTÁ SUJETO A REVISIÓN POR PARTE DE LOS TÉCNICOS DE LA GOBERNACIÓN DEL BENI.
    </div>

    <div class="section-title">DATOS DE LA TRANSMISIÓN</div>
    <table>
        <tr>
            <th>Tipo de Contribuyente</th>
            <td>{{ $tipo_contribuyente ?? 'Natural' }}</td>
        </tr>
        <tr>
            <th>Base Imponible Declarada</th>
            <td>Bs. {{ number_format($base_imponible ?? $base, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <th>Fecha de Transmisión</th>
            <td>{{ $fecha_transmision }}</td>
        </tr>
        <tr>
            <th>Fecha de Vencimiento</th>
            <td>{{ $fecha_vencimiento }} (90 días calendario)</td>
        </tr>
        <tr>
            <th>UFV de Pago Aplicada</th>
            <td>{{ number_format($ufv_pago ?? 0, 5) }}</td>
        </tr>
    </table>

    <div class="section-title">DETALLE DE LA DEUDA TRIBUTARIA (CÁLCULO LEY 812)</div>
    <table class="boleta-table">
        <tr>
            <td>Tributo Omitido (S900)</td>
            <td class="text-right bold">Bs. {{ number_format($idtgb_base, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Mantenimiento de Valor (S920)</td>
            <td class="text-right">Bs. {{ number_format($mantenimiento_valor, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Intereses Moratorios (S930)</td>
            <td class="text-right">Bs. {{ number_format($interes, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Multa por Incumplimiento (S900)</td>
            <td class="text-right">Bs. {{ number_format($multa_idf, 2, ',', '.') }}</td>
        </tr>
        @if($dias_mora > 0)
        <tr>
            <td colspan="2" style="color:red; font-size: 9px;">* Cálculo realizado con {{ $dias_mora }} días de mora.</td>
        </tr>
        @endif
    </table>

    <div class="total-box">
        <strong>TOTAL DEUDA ESTIMADA: Bs. {{ number_format($final, 2, ',', '.') }}</strong>
    </div>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i:s') }} | Código de Verificación: {{ substr(md5(time()), 0, 8) }}<br>
        © 2026 Gobierno Autónomo Departamental del Beni
    </div>
</body>
</html>
