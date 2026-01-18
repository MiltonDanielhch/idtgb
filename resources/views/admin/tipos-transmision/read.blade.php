@extends('voyager::master')

@section('page_title', 'Ver Tipo de Transmisión')

@section('page_header')
    <h1 class="page-title">
        <i class="fa-solid fa-arrow-right-arrow-left"></i> Ver Tipo de Transmisión
        <a href="{{ route('admin.tipos-transmision.index') }}" class="btn btn-warning">
            <i class="voyager-list"></i> <span class="hidden-xs hidden-sm">Volver a la lista</span>
        </a>
        @can('update', $tipoTransmision)
            <a href="{{ route('admin.tipos-transmision.edit', $tipoTransmision->id) }}" class="btn btn-primary">
                <i class="voyager-edit"></i> <span class="hidden-xs hidden-sm">Editar</span>
            </a>
        @endcan
    </h1>
@stop

@section('content')
    <div class="page-content read container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered" style="padding-bottom:5px;">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="panel-heading" style="border-bottom:0;">
                                    <h3 class="panel-title">Detalles del Tipo de Transmisión</h3>
                                </div>
                                <div class="panel-body" style="padding-top:0;">
                                    <p><strong>ID:</strong> {{ $tipoTransmision->id }}</p>
                                    <p><strong>Nombre:</strong> {{ $tipoTransmision->nombre }}</p>
                                    <p><strong>Creado:</strong> {{ $tipoTransmision->created_at->format('d/m/Y H:i') }} ({{ $tipoTransmision->created_at->diffForHumans() }})</p>
                                    <p><strong>Actualizado:</strong> {{ $tipoTransmision->updated_at->format('d/m/Y H:i') }} ({{ $tipoTransmision->updated_at->diffForHumans() }})</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="panel-heading" style="border-bottom:0;">
                                    <h3 class="panel-title">Auditoría</h3>
                                </div>
                                <div class="panel-body" style="padding-top:0;">
                                    <p><strong>Creado por:</strong> {{ $tipoTransmision->createdBy->name ?? 'N/A' }}</p>
                                    <p><strong>Actualizado por:</strong> {{ $tipoTransmision->updatedBy->name ?? 'N/A' }}</p>
                                    <p><strong>Tasas asociadas:</strong> <span class="badge badge-primary">{{ $tipoTransmision->tasas_count ?? $tipoTransmision->tasas->count() }}</span></p>
                                    <p><strong>Trámites asociados:</strong> <span class="badge badge-info">{{ $tipoTransmision->tramites_count ?? $tipoTransmision->tramites->count() }}</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
