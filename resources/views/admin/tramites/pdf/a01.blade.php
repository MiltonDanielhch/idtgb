<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Formulario A-01 - IDTGB Beni</title>
    <style>
        body{font-family:Arial,sans-serif;font-size:13px;margin:30px;}
        .header{text-align:center;border-bottom:2px solid #000;padding-bottom:10px;margin-bottom:20px;}
        .table{width:100%;border-collapse:collapse;margin-bottom:15px;}
        .table th,.table td{border:1px solid #ccc;padding:6px;}
        .table th{background:#f5f5f5;}
        .footer{margin-top:30px;font-size:11px;text-align:center;}
    </style>
</head>
<body>
    <div class="header">
        <h2>FORMULARIO A-01</h2>
        <h3>Impuesto Departamental a la Transmisión Gratuita de Bienes - IDTGB</h3>
        <p>Departamento de Beni - Bolivia</p>
    </div>

    <h4>1. Datos del Trámite</h4>
    <table class="table">
        <tr><th>Nro Trámite</th><td>{{ $tramite->nro_tramite }}</td></tr>
        <tr><th>Fecha Presentación</th><td>{{ $tramite->fecha_presentacion->format('d/m/Y') }}</td></tr>
        <tr><th>Fecha Transmisión</th><td>{{ $tramite->fecha_transmision->format('d/m/Y') }}</td></tr>
        <tr><th>Tipo de Transmisión</th><td>{{ $tramite->tipoTransmision->nombre }}</td></tr>
    </table>

    <h4>2. Inmueble</h4>
    <table class="table">
        @php
            $inmueble = $tramite->inmuebles->first();
        @endphp
        <tr><th>Catástro</th><td>{{ optional($inmueble)->catastro ?? 'No asignado' }}</td></tr>
        <tr><th>Dirección</th><td>{{ optional($inmueble)->direccion ?? '-' }}</td></tr>
        <tr><th>Valor Catastral</th><td>Bs {{ number_format(optional($inmueble)->valor_catastral ?? 0, 2) }}</td></tr>
    </table>

    <h4>3. Base Imponible y Cálculo del IDTGB</h4>
    <table class="table">
        <tr><th>Base Imponible</th><td>Bs {{ number_format($tramite->base_imponible, 2) }}</td></tr>
        <tr><th>Total IDTGB</th><td>Bs {{ number_format($tramite->total_idtgb, 2) }}</td></tr>
        <tr><th>Recargo por Mora</th><td>Bs {{ number_format($tramite->recargo_mora, 2) }}</td></tr>
        <tr><th><strong>Monto Final</strong></th><td><strong>Bs {{ number_format($tramite->monto_final, 2) }}</strong></td></tr>
        <tr><th>UFV Aplicada</th><td>{{ $tramite->ufv_aplicada }}</td></tr>
    </table>

    <h4>4. Adquirentes</h4>
    <table class="table">
        <thead>
            <tr><th>Nombre</th><th>Parentesco</th><th>%</th><th>Tasa Aplicada</th><th>IDTGB Proporcional</th></tr>
        </thead>
        <tbody>
            @foreach($tramite->adquirentes as $a)
            <tr>
                <td>{{ $a->persona->first_name.' '.$a->persona->paternal_surname }}</td>
                <td>{{ $a->parentesco->nombre }}</td>
                <td>{{ $a->porcentaje }} %</td>
                <td>{{ $a->tasa_aplicada }} %</td>
                <td>Bs {{ number_format($a->idtgb_proporcional, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <h4>5. Disponentes</h4>
    <table class="table">
        <thead><tr><th>Nombre</th><th>Tipo</th><th>Fallecimiento</th></tr></thead>
        <tbody>
            @foreach($tramite->disponentes as $d)
            <tr>
                <td>{{ $d->persona->first_name.' '.$d->persona->paternal_surname }}</td>
                <td>{{ $d->tipo }}</td>
                <td>{{ optional($d->fecha_fallecimiento)->format('d/m/Y') ?? 'Vivo' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <h4>6. Exenciones Aplicadas</h4>
    <ul>
        @forelse($tramite->exenciones as $e)
            <li>{{ optional($e->exencion)->nombre ?? 'Exención no encontrada' }} - Bs {{ number_format($e->monto_aplicado, 2) }}</li>
        @empty
            <li>Ninguna</li>
        @endforelse
    </ul>

    <div style="margin-top: 40px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tbody>
                <tr>
                    <td style="width: 70%; vertical-align: top; border: none; padding: 0;">
                        <h4>7. Validación</h4>
                        <p style="font-size: 10px; margin: 0; word-break: break-all;">
                            <strong>Hash:</strong> {{ $hash }}
                        </p>
                        <p style="font-size: 11px; margin-top: 10px;">
                            Verifique la autenticidad de este documento en:<br>
                            <a href="{{ route('tramite.validar', ['hash' => $hash]) }}">{{ route('tramite.validar', ['hash' => $hash]) }}</a>
                        </p>
                    </td>
                    <td style="width: 30%; text-align: right; vertical-align: top; border: none; padding: 0;">
                        <img src="data:image/svg+xml;base64,{{ $qr }}" alt="QR Code" style="width: 120px; height: 120px;">
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div style="margin-top:40px; text-align:right;">
        <p>_________________________</p>
        <small>Funcionario IDTGB - Beni</small>
    </div>

    <div class="footer">
        <p>Formulario generado el {{ now()->format('d/m/Y H:i') }}</p>
        <p>Usuario: {{ auth()->user()->name }}</p>
    </div>
</body>
</html>
