@extends('layouts.app')

@section('title', 'Guía Rápida para Ciudadanos')

@push('styles')
<style>
    /* Estilos para mejorar la legibilidad del tutorial generado desde Markdown */
    .tutorial-content h1, .tutorial-content h2, .tutorial-content h3 {
        margin-top: 1.5rem;
        margin-bottom: 1rem;
        font-weight: 600;
    }
    .tutorial-content img {
        max-width: 100%;
        height: auto;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 5px;
        margin-top: 1rem;
        margin-bottom: 1rem;
    }
</style>
@endpush

@section('content')
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-body tutorial-content">
            {!! $content !!}
        </div>
    </div>
</div>
@endsection
