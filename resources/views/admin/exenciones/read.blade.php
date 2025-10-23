@extends('voyager::master')

@section('page_title', 'Ver Exención')

@section('page_header')
    <div class="container-fluid">
        <h1 class="page-title">
            <i class="voyager-eye"></i> Ver Exención
        </h1>
        @include('voyager::alerts')
        @if(session('message'))
            <div class="alert alert-{{ session('alert-type', 'info') }} alert-dismissible auto-dismiss">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                {{ session('message') }}
            </div>
        @endif
    </div>
@stop

@section('content')
<div class="page-content container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="voyager-tag"></i> Detalle de la Exención
                    </h3>
                </div>

                <div class="panel-body" style="padding: 20px;">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <td><strong>ID</strong></td>
                                        <td>{{ $exencion->id }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Nombre</strong></td>
                                        <td>{{ $exencion->nombre }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tipo</strong></td>
                                        <td>{{ ucfirst($exencion->tipo) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Valor</strong></td>
                                        <td>
                                            {{ $exencion->tipo === 'porcentaje'
                                                ? number_format($exencion->valor, 2) . ' %'
                                                : 'Bs ' . number_format($exencion->valor, 2) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <td><strong>Monto Máximo</strong></td>
                                        <td>
                                            {{ $exencion->monto_maximo
                                                ? 'Bs ' . number_format($exencion->monto_maximo, 2)
                                                : 'Sin tope' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Vigente Desde</strong></td>
                                        <td>
                                            {{ optional($exencion->vigente_desde)->format('d/m/Y') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Vigente Hasta</strong></td>
                                        <td>
                                            {{ $exencion->vigente_hasta
                                                ? $exencion->vigente_hasta->format('d/m/Y')
                                                : 'Indefinido' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Creado</strong></td>
                                        <td>{{ optional($exencion->created_at)->format('d/m/Y H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Actualizado</strong></td>
                                        <td>{{ optional($exencion->updated_at)->format('d/m/Y H:i') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if($exencion->tramites && $exencion->tramites->isNotEmpty())
                        <div class="row" style="margin-top: 30px;">
                            <div class="col-md-12">
                                <h4><i class="voyager-list"></i> Trámites que usan esta exención</h4>
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th># Trámite</th>
                                            <th>Fecha</th>
                                            <th>Monto Aplicado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($exencion->tramites as $t)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('admin.tramites.show', $t) }}" target="_blank">
                                                        {{ $t->nro_tramite }}
                                                    </a>
                                                </td>
                                                <td>{{ $t->created_at->format('d/m/Y') }}</td>
                                                <td>Bs {{ number_format($t->pivot->monto_aplicado, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info" style="margin-top: 30px;">
                            <i class="voyager-info-circled"></i> Esta exención **no ha sido usada** en ningún trámite aún.
                        </div>
                    @endif
                </div>

                <div class="panel-footer text-right">
                    @can('update', $exencion)
                        <a href="{{ route('admin.exenciones.edit', $exencion) }}" class="btn btn-primary">
                            <i class="voyager-edit"></i> Editar
                        </a>
                    @endcan
                    <a href="{{ route('admin.exenciones.index') }}" class="btn btn-default">
                        <i class="voyager-angle-left"></i> Volver al listado
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('javascript')
<script>
    $(document).ready(function () {
        // Auto-dismiss alerts
        setTimeout(() => $('.auto-dismiss').fadeOut('slow', function(){ $(this).remove(); }), 5000);
        $('.auto-dismiss .close').click(function(e){
            e.preventDefault();
            $(this).closest('.alert').fadeOut('slow', function(){ $(this).remove(); });
        });
    });
</script>
@stop
