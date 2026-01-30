@extends('layouts.app')

@section('title', 'Portal Ciudadano | IDTGB - GAD Beni')

@section('content')
<style>
    /* Definición de colores de Beni para el ejemplo. Asume que se define en el CSS global */
    :root {
        --beni-green: #008000; /* Verde de la bandera */
        --beni-yellow: #FFD700; /* Amarillo de la bandera */
        --beni-blue: #ee7606; /* Azul para contraste */
    }
    .display-5 {
        font-family: 'Inter', sans-serif;
    }
    .card-icon {
        color: var(--beni-yellow);
        text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    }
</style>
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
                        {{-- CORREGIDO: Las tasas se ajustan a la Ley Departamental del IDTGB (1%, 10%, 20%) --}}
                        <li>Estimación con las alícuotas legales del Beni (1%, 10%, 20%)</li>
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
                <div class="card-header bg-secondary text-white">
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
@endsection
