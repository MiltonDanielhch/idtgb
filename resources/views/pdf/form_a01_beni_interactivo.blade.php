<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>IDTGB Beni - Form A-01 Cálculo</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; margin: 30px; color: #333; }
        .header { text-align: center; margin-bottom: 25px; }
        .header h2 { margin: 0; font-size: 18px; font-weight: 700; }
        .header h3 { margin: 2px 0 15px; font-size: 16px; font-weight: 600; }
        .section { margin-bottom: 20px; }
        .section p { margin: 4px 0; }
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th, .table td { border: 1px solid #000; padding: 8px 6px; }
        .table th { background: #f2f2f2; font-weight: 700; }
        .text-right { text-align: right; }
        .total { font-size: 1.25rem; font-weight: 700; }
    </style>
</head>
<body>

<div class="header">
    <h2>GOBIERNO AUTÓNOMO DEPARTAMENTAL DEL BENI</h2>
    <h3>Impuesto Departamental a la Transmisión Gratuita de Bienes (IDTGB)<br>Formulario A-01 Cálculo</h3>
</div>

<!-- DATOS DEL CONTRIBUYENTE -->
<div class="section">
    <p><strong>Sujeto Pasivo:</strong> {{ $persona->first_name }} {{ $persona->paternal_surname }} {{ $persona->maternal_surname ?? '' }}</p>
    <p><strong>C.I./NIT:</strong> {{ $persona->ci ?? $persona->nit }}</p>
    <p><strong>Inmueble:</strong> {{ $inmueble->catastro }} - {{ $inmueble->direccion }}</p>
    <p><strong>Tipo de Transmisión:</strong> {{ $tipo_transmision }}</p>
    <p><strong>Fecha de Transmisión:</strong> {{ $fecha_transmision }}</p>
    <p><strong>Fecha de Vencimiento:</strong> {{ $fecha_vencimiento }}</p>
    <p><strong>Días de Mora:</strong> {{ $dias_mora }}</p>
    <p><strong>Base Imponible:</strong> Bs. {{ number_format($base_imponible, 2, ',', '.') }}</p>
    <p><strong>Alicuota Aplicada:</strong> {{ $tasa }}%</p>
</div>

<!-- TABLA DE RESULTADOS -->
<table class="table">
    <thead>
        <tr>
            <th>Concepto</th>
            <th class="text-right">Monto (Bs.)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Tributo Omitido</td>
            <td class="text-right">{{ number_format($tributo_omitido, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Mantenimiento de Valor</td>
            <td class="text-right">0,00</td>
        </tr>
        <tr>
            <td>Intereses Moratorios</td>
            <td class="text-right">0,00</td>
        </tr>
        <tr>
            <td>Multa IDF</td>
            <td class="text-right">0,00</td>
        </tr>
        <tr class="total">
            <td><strong>Total (Bs.)</strong></td>
            <td class="text-right"><strong>{{ number_format($monto_final, 2, ',', '.') }}</strong></td>
        </tr>
    </tbody>
</table>

<!-- DATOS BANCARIOS -->
<p style="margin-top: 25px;"><strong>Nº de Cuenta Banco Unión:</strong> {{ $cuenta_banco }}</p>
<p><em>Este cálculo es referencial y no reemplaza el trámite oficial ante la GAD-Beni.</em></p>

</body>
</html>
