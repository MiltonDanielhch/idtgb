<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Portal Ciudadano | IDTGB - GAD Beni</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --beni-green: #007A33;
            --beni-green-light: #00A652;
            --beni-yellow: #FCD116;
        }
        body {
            background-color: #f8f9fa;
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
        }
        .navbar-custom {
            background: linear-gradient(135deg, var(--beni-green), var(--beni-green-light));
        }
        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link {
            color: #fff !important;
        }
        .card-icon {
            font-size: 1.5rem;
            margin-right: 0.5rem;
        }
        .footer {
            background-color: #f1f3f5;
            padding: 20px 0;
            margin-top: 40px;
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-custom shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="/">
            <i class="fas fa-landmark me-2"></i>
            GAD Beni - IDTGB
        </a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="/calculadora-idtgb-beni">
                <i class="fas fa-calculator me-1"></i>Calculadora
            </a>
            <a class="nav-link" href="/admin/login">
                <i class="fas fa-lock me-1"></i>Funcionarios
            </a>
        </div>
    </div>
</nav>

<!-- MAIN CONTENT -->
<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold" style="color: var(--beni-green);">
            <i class="fas fa-building me-2"></i>
            GOBIERNO AUTÓNOMO DEPARTAMENTAL DEL BENI
        </h1>
        <p class="lead text-muted">
            Impuesto Departamental a la Transmisión Gratuita de Bienes (IDTGB)
        </p>
    </div>

    <div class="row g-4">
        <!-- CIUDADANOS -->
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm border-success">
                <div class="card-header bg-success text-white">
                    <h3 class="h5 mb-0">
                        <i class="fas fa-user card-icon"></i>
                        Para ciudadanos
                    </h3>
                </div>
                <div class="card-body d-flex flex-column">
                    <p class="mb-3">
                        ¿Desea estimar el monto del IDTGB por herencia, donación o legado?
                        Use nuestra calculadora rápida para obtener una orientación preliminar.
                    </p>
                    <div class="alert alert-warning mb-3">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <small>
                            <strong>Atención:</strong> Este cálculo es estimado y orientativo.
                            El trámite oficial se realiza personalmente en las oficinas de la Gobernación del Beni.
                        </small>
                    </div>
                    <ul class="small mb-4">
                        <li>Estimación con tasas reales del Beni (0%, 1.5%, 3%, 5%)</li>
                        <li>Sin necesidad de registro ni login</li>
                        <li>Descarga de resultado en PDF para su referencia</li>
                    </ul>
                    <div class="mt-auto">
                        <a href="/calculadora-idtgb-beni" class="btn btn-success w-100">
                            <i class="fas fa-calculator me-2"></i>Calcular IDTGB
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- FUNCIONARIOS -->
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm border-primary">
                <div class="card-header bg-primary text-white">
                    <h3 class="h5 mb-0">
                        <i class="fas fa-user-tie card-icon"></i>
                        Para funcionarios
                    </h3>
                </div>
                <div class="card-body d-flex flex-column">
                    <p class="mb-3">
                        Acceda al sistema integral de gestión de trámites IDTGB para:
                    </p>
                    <ul class="small mb-4">
                        <li>Crear y registrar trámites oficiales</li>
                        <li>Gestionar adquirentes, disponentes e inmuebles</li>
                        <li>Subir y validar documentos</li>
                        <li>Registrar pagos y generar Formulario A-01 oficial</li>
                        <li>Exportar trámites finalizados al SIN</li>
                    </ul>
                    <div class="mt-auto">
                        <a href="/admin/login" class="btn btn-primary w-100">
                            <i class="fas fa-lock me-2"></i>Ingresar al sistema
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MARCO LEGAL -->
    <div class="text-center mt-4">
        <small class="text-muted">
            <i class="fas fa-gavel me-1"></i>
            <a href="#" class="text-decoration-none">Marco legal del IDTGB - Departamento del Beni</a>
        </small>
    </div>
</div>

<!-- FOOTER -->
<footer class="footer">
    <div class="container text-center">
        <p class="mb-0">
            <small>
                © {{ date('Y') }} Gobierno Autónomo Departamental del Beni |
                Dirección de Recaudaciones |
                Todos los derechos reservados
            </small>
        </p>
        <p class="mb-0 mt-1">
            <small class="text-muted">
                Sistema de Gestión Tributaria IDTGB v1.0
            </small>
        </p>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
