<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif; /* ✅ Fuente compatible con DomPDF */
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #007A33;
            padding-bottom: 10px;
        }
        .header h2 {
            color: #007A33;
            margin: 5px 0;
            font-size: 18px;
        }
        .header p {
            margin: 3px 0;
            font-size: 14px;
            color: #333;
        }
        .disclaimer {
            background-color: #fff3cd;
            padding: 12px;
            border: 1px solid #ffeaa7;
            color: #856404;
            font-weight: bold;
            margin: 15px 0;
            text-align: center;
            border-radius: 4px;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            color: #007A33;
            margin: 15px 0 10px;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f0f8f0;
            font-weight: bold;
        }
        .total {
            font-size: 16px;
            font-weight: bold;
            color: #007A33;
            text-align: right;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px solid #007A33;
        }
        .footer {
            margin-top: 25px;
            font-size: 10px;
            color: #666;
            text-align: center;
            border-top: 1px dashed #ccc;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>GOBIERNO AUTÓNOMO DEPARTAMENTAL DEL BENI</h2>
        <p>Impuesto Departamental a la Transmisión Gratuita de Bienes (IDTGB)</p>
        <p><strong>CÁLCULO ESTIMADO - INFORMATIVO</strong></p>
    </div>

    <div class="disclaimer">
        ⚠️ ESTE DOCUMENTO ES UNA SIMULACIÓN DE CÁLCULO Y NO TIENE VALIDEZ OFICIAL.<br>
        NO SUSTITUYE EL TRÁMITE FISCAL ANTE LA AUTORIDAD TRIBUTARIA DEL BENI.
    </div>

    <div class="title">Resultado de Cálculo Estimado</div>

    <table>
        <tr>
            <th>Tipo de contribuyente</th>
            <td>{{ $tipo_contribuyente ?? 'No especificado' }}</td>
        </tr>
        <tr>
            <th>Inmueble (Catastro)</th>
            <td>
                {{ $inmueble->catastro ?? '—' }}<br>
                {{ $inmueble->direccion ?? 'Sin dirección' }},
                {{ $inmueble->municipio?->nombre ?? 'Sin municipio' }}
            </td>
        </tr>
        <tr>
            <th>Tipo de transmisión</th>
            <td>
                @if($tipo_transmision == 'Entre vivos')
                    Donación (entre vivos)
                @elseif($tipo_transmision == 'Testamento')
                    Sucesión (herencia/testamento)
                @else
                    {{ $tipo_transmision ?? 'No especificado' }}
                @endif
            </td>
        </tr>
        <tr>
            <th>Fecha de transmisión</th>
            <td>{{ $fecha_transmision ?? '—' }}</td>
        </tr>
        <tr>
            <th>Fecha de vencimiento</th>
            <td>{{ $fecha_vencimiento ?? '—' }} (30 días hábiles)</td>
        </tr>
        <tr>
            <th>Base imponible (Bs.)</th>
            <td>{{ number_format($base_imponible ?? 0, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <th>Parentesco declarado</th>
            <td>{{ $parentesco?->nombre ?? 'No especificado' }}</td>
        </tr>
        <tr>
            <th>Tasa del IDTGB aplicable (Beni)</th>
            <td>{{ number_format($tasa ?? 0, 2) }}%</td>
        </tr>
        <tr>
            <th>Impuesto estimado (Bs.)</th>
            <td>{{ number_format($monto_final ?? 0, 2, ',', '.') }}</td>
        </tr>
    </table>

    <div class="total">
        Monto estimado a pagar: Bs. {{ number_format($monto_final ?? 0, 2, ',', '.') }}
    </div>

    <div class="footer">
        Fecha de cálculo: {{ now()->format('d/m/Y H:i') }} |<br>
        Sistema de Cálculo Rápido - IDTGB Beni |<br>
        Valores sujetos a verificación oficial
    </div>
</body>
</html>
