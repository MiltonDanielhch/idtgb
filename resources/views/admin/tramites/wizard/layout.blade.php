{{-- resources/views/admin/tramites/wizard/layout.blade.php --}}
@extends('voyager::master')

@section('page_title', $page_title ?? 'Asistente de Trámites')

@section('css')
    <style>
        .wizard-progress {
            margin-bottom: 30px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            position: relative;
        }
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: #dee2e6;
            z-index: 1;
        }
        .step {
            text-align: center;
            z-index: 2;
            position: relative;
        }
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #dee2e6;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 5px;
            font-weight: bold;
        }
        .step.active .step-number {
            background: #3490dc;
            color: white;
        }
        .step.completed .step-number {
            background: #38c172;
            color: white;
        }
        .step-label {
            font-size: 12px;
            color: #6c757d;
        }
        .step.active .step-label {
            color: #3490dc;
            font-weight: bold;
        }
    </style>
@stop

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    {{-- Barra de progreso --}}
    <div class="wizard-progress">
        <div class="step-indicator">
            <div class="step {{ $current_step >= 1 ? 'active' : '' }} {{ $current_step > 1 ? 'completed' : '' }}">
                <div class="step-number">1</div>
                <div class="step-label">Datos Generales</div>
            </div>
            <div class="step {{ $current_step >= 2 ? 'active' : '' }} {{ $current_step > 2 ? 'completed' : '' }}">
                <div class="step-number">2</div>
                <div class="step-label">Disponentes</div>
            </div>
            <div class="step {{ $current_step >= 3 ? 'active' : '' }} {{ $current_step > 3 ? 'completed' : '' }}">
                <div class="step-number">3</div>
                <div class="step-label">Adquirentes</div>
            </div>
            <div class="step {{ $current_step >= 4 ? 'active' : '' }} {{ $current_step > 4 ? 'completed' : '' }}">
                <div class="step-number">4</div>
                <div class="step-label">Inmuebles</div>
            </div>
            <div class="step {{ $current_step >= 5 ? 'active' : '' }}">
                <div class="step-number">5</div>
                <div class="step-label">Resumen</div>
            </div>
        </div>

        {{-- Barra de progreso lineal --}}
        <div class="mt-3">
            <div class="progress" style="height: 8px;">
                <div class="progress-bar" role="progressbar" style="width: {{ $progress }}%;"
                     aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                </div>
            </div>
            <div class="d-flex justify-content-between mt-2">
                <small class="text-muted">Paso {{ $current_step }} de {{ $total_steps }}</small>
                <small class="text-muted">{{ $progress }}% completado</small>
            </div>
        </div>
    </div>

    {{-- Contenido específico de cada paso --}}
    @yield('wizard-content')
</div>
@stop
