<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Trámite - G.A.D. BENI</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f4f7f6;
        }
        .container {
            max-width: 700px;
        }
        .verification-card {
            border-left: 5px solid #198754;
        }
        .header {
            background-color: #00492d;
            color: white;
            padding: 1rem;
            text-align: center;
            border-radius: 0.25rem 0.25rem 0 0;
        }
        .footer {
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="header">
                <h4><img src="/images/logo-gobernacion.png" height="40" alt="Logo Gobernación"> Verificación de Documento</h4>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-success verification-card" role="alert">
                    <h5 class="alert-heading">Trámite Válido</h5>
                    <p class="mb-0">El documento con el código de verificación proporcionado ha sido encontrado en el sistema del Gobierno Autónomo Departamental del Beni.</p>
                </div>

                <h5 class="mt-4">Detalles del Trámite</h5>
                <hr>

                <dl class="row">
                    <dt class="col-sm-4">Número de Trámite</dt>
                    <dd class="col-sm-8"><strong>{{ $tramite->nro_tramite }}</strong></dd>

                    <dt class="col-sm-4">Fecha de Presentación</dt>
                    <dd class="col-sm-8">{{ $tramite->fecha_presentacion->format('d/m/Y') }}</dd>

                    <dt class="col-sm-4">Tipo de Transmisión</dt>
                    <dd class="col-sm-8">{{ $tramite->tipoTransmision->nombre }}</dd>

                    <dt class="col-sm-4">Adquirente Principal</dt>
                    <dd class="col-sm-8">{{ optional(optional($tramite->adquirentes->first())->person)->display_name ?? optional(optional($tramite->adquirentes->first())->person)->full_name ?? 'No especificado' }}</dd>

                    <dt class="col-sm-4">Monto del Impuesto</dt>
                    <dd class="col-sm-8">Bs. {{ number_format($tramite->total_idtgb, 2) }}</dd>

                    <dt class="col-sm-4">Estado Actual</dt>
                    <dd class="col-sm-8">
                        @php $badge = match($tramite->estado) { 'Pagado' => 'success', 'Borrador' => 'secondary', 'Observado' => 'warning', 'Anulado' => 'danger', 'Finalizado' => 'primary', default => 'info' }; @endphp
                        <span class="badge bg-{{ $badge }}">{{ $tramite->estado }}</span>
                    </dd>
                </dl>

            </div>
            <div class="card-footer text-center footer">
                <p class="mb-1">Gobernación Autónoma Departamental del Beni | Dirección de Ingresos</p>
                <p class="mb-0">Generado el: {{ now()->format('d/m/Y H:i:s') }}</p>
            </div>
        </div>
    </div>
</body>
</html>
