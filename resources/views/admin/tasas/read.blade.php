@extends('voyager::master')

@section('page_title', 'Ver Tasa')

@section('content')
<div class="page-content container-fluid">
    <div class="panel panel-bordered panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">
                <i class="fa-solid fa-percent"></i> Ver Tasa: {{ number_format($tasa->tasa, 2) }} %
            </h3>
        </div>

        <div class="panel-body">
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th style="width: 200px;">ID</th>
                                <td>{{ $tasa->id }}</td>
                            </tr>
                            <tr>
                                <th>Departamento</th>
                                <td>{{ $tasa->departamento->nombre }}</td>
                            </tr>
                            <tr>
                                <th>Parentesco</th>
                                <td>{{ $tasa->parentesco->nombre }}</td>
                            </tr>
                            <tr>
                                <th>Tipo de Transmisión</th>
                                <td>{{ $tasa->tipoTransmision->nombre ?? 'Ninguno' }}</td>
                            </tr>
                            <tr>
                                <th>Tasa (%)</th>
                                <td>
                                    <span class="badge badge-primary" style="font-size: 1.1em;">
                                        {{ number_format($tasa->tasa, 2) }} %
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Vigente Desde</th>
                                <td>{{ $tasa->vigente_desde->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <th>Vigente Hasta</th>
                                <td>
                                    @if($tasa->vigente_hasta)
                                        {{ $tasa->vigente_hasta->format('d/m/Y') }}
                                    @else
                                        <span class="text-muted">Sin fecha límite</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Creado</th>
                                <td>{{ $tasa->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th>Actualizado</th>
                                <td>{{ $tasa->updated_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="panel-footer text-right">
            <a href="{{ route('admin.tasas.index') }}" class="btn btn-default">
                <i class="voyager-angle-left"></i> Volver
            </a>

            @can('update', $tasa)
                <a href="{{ route('admin.tasas.edit', $tasa) }}" class="btn btn-primary">
                    <i class="voyager-edit"></i> Editar
                </a>
            @endcan
        </div>
    </div>
</div>
@stop
