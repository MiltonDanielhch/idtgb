<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Comprobante de Pago - IDTGB Beni</title>
    <style>
        body{font-family:Arial,sans-serif;font-size:13px;margin:40px;}
        .header{text-align:center;border-bottom:2px solid #000;padding-bottom:10px;margin-bottom:20px;}
        .table{width:100%;border-collapse:collapse;margin-bottom:15px;}
        .table th,.table td{border:1px solid #ccc;padding:8px;}
        .table th{background:#f5f5f5;}
        .code{font-family:monospace;font-size:18px;letter-spacing:4px;}
    </style>
</head>
<body>
    <div class="header">
        <h2>COMPROBANTE DE PAGO</h2>
        <h3>IDTGB - Departamento de Beni</h3>
    </div>

    <table class="table">
        <tr><th>Nro Trámite</th><td>{{ $pago->tramite->nro_tramite }}</td></tr>
        <tr><th>Fecha de Pago</th><td>{{ \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y H:i') }}</td></tr>
        <tr><th>Monto Pagado</th><td><strong>Bs {{ number_format($pago->monto, 2) }}</strong></td></tr>
        <tr><th>Banco</th><td>{{ $pago->banco }}</td></tr>
        <tr><th>Nro Operación</th><td>{{ $pago->nro_operacion }}</td></tr>
        <tr><th>Código de Barras</th><td class="code">{{ $pago->codigo_barras }}</td></tr>
    </table>

    <div style="text-align:center;margin-top:30px;">
        {!! DNS1D::getBarcodeHTML($pago->codigo_barras, 'C128', 2, 50) !!}
    </div>

    <div style="text-align:center;margin-top:40px;font-size:11px;">
        <p>Generado el {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()->name }}</p>
        <p>Conservar este comprobante como respaldo del pago.</p>
    </div>
</body>
</html>
