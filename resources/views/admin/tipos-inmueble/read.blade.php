@extends('voyager::master')

@section('page_title', 'Ver Tipo de Inmueble')

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-home"></i> Ver Tipo de Inmueble
        <a href="{{ route('admin.tipos-inmueble.index') }}" class="btn btn-warning">
            <i class="voyager-list"></i> <span class="hidden-xs hidden-sm">Volver a la lista</span>
        </a>
    </h1>
@stop

@section('content')
    <div class="page-content read container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered" style="padding-bottom:5px;">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="panel-heading" style="border-bottom:0;">
                                    <h3 class="panel-title">Detalles del Tipo de Inmueble</h3>
                                </div>
                                <div class="panel-body" style="padding-top:0;">
                                    <p><strong>ID:</strong> {{ $tipoInmueble->id }}</p>
                                    <p><strong>Nombre:</strong> {{ $tipoInmueble->nombre }}</p>
                                    <p><strong>Creado:</strong> {{ $tipoInmueble->created_at->format('d/m/Y H:i') }} ({{ $tipoInmueble->created_at->diffForHumans() }})</p>
                                    @if($tipoInmueble->createdBy)
                                        <p><strong>Creado por:</strong> {{ $tipoInmueble->createdBy->name ?? $tipoInmueble->createdBy->email }}</p>
                                    @endif
                                    <p><strong>Actualizado:</strong> {{ $tipoInmueble->updated_at->format('d/m/Y H:i') }} ({{ $tipoInmueble->updated_at->diffForHumans() }})</p>
                                    @if($tipoInmueble->updatedBy)
                                        <p><strong>Actualizado por:</strong> {{ $tipoInmueble->updatedBy->name ?? $tipoInmueble->updatedBy->email }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
