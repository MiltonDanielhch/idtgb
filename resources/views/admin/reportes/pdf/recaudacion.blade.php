<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .header h2 {
            margin: 0;
            font-size: 14px;
            font-weight: normal;
        }
        .details p {
            margin: 2px 0;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .text-right {
            text-align: right;
        }
        .footer {
            text-align: right;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ setting('admin.title', 'GAD BENI') }}</h1>
        <h2>{{ $titulo }}</h2>
    </div>

    <div class="details">
        <p><strong>Periodo del reporte:</strong> {{ $fecha_inicio }} al {{ $fecha_fin }}</p>
        <p><strong>Fecha de generación:</strong> {{ $fecha_generacion }}</p>
        <p><strong>Total Recaudado:</strong> {{ number_format($total_recaudado, 2, ',', '.') }} Bs.</p>
    </div>

    <table>
        <thead>
            <tr>
                <th># Trámite</th>
                <th>Fecha de Finalización</th>
                <th>Adquirente Principal</th>
                <th class="text-right">Monto Final (Bs.)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tramites as $tramite)
                <tr>
                    <td>{{ $tramite->nro_tramite }}</td>
                    <td>{{ $tramite->updated_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $tramite->adquirentes->first()->persona->nombre_completo ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format($tramite->monto_final, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center;">No se encontraron registros en el periodo seleccionado.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="footer">Total General:</td>
                <td class="footer text-right">{{ number_format($total_recaudado, 2, ',', '.') }} Bs.</td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
