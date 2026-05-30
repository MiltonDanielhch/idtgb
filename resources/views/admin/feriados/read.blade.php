@extends('voyager::master')

@section('page_title', 'Detalle de Feriado')

@section('content')
<div class="page-content container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="voyager-calendar"></i> Detalle de Feriado
                    </h3>
                </div>

                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-striped">
                                <tr>
                                    <th width="30%">ID:</th>
                                    <td>{{ $feriado->id }}</td>
                                </tr>
                                <tr>
                                    <th>Fecha:</th>
                                    <td>{{ $feriado->fecha->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Nombre:</th>
                                    <td>{{ $feriado->nombre }}</td>
                                </tr>
                                <tr>
                                    <th>Tipo:</th>
                                    <td>{{ $feriado->tipo }}</td>
                                </tr>
                                <tr>
                                    <th>Departamento:</th>
                                    <td>{{ $feriado->departamento ? $feriado->departamento->nombre : 'Nacional' }}</td>
                                </tr>
                                <tr>
                                    <th>Estado:</th>
                                    <td>
                                        @if($feriado->activo)
                                            <span class="badge badge-success">Activo</span>
                                        @else
                                            <span class="badge badge-secondary">Inactivo</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-striped">
                                <tr>
                                    <th width="30%">Creado por:</th>
                                    <td>{{ $feriado->createdBy ? $feriado->createdBy->name : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Fecha de creación:</th>
                                    <td>{{ $feriado->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Actualizado por:</th>
                                    <td>{{ $feriado->updatedBy ? $feriado->updatedBy->name : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Fecha de actualización:</th>
                                    <td>{{ $feriado->updated_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                @if($feriado->deleted_at)
                                <tr>
                                    <th>Fecha de eliminación:</th>
                                    <td>{{ $feriado->deleted_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>

                <div class="panel-footer text-right">
                    <a href="{{ route('admin.feriados.index') }}" class="btn btn-default">
                        <i class="voyager-list"></i> Volver al Listado
                    </a>
                    <a href="{{ route('admin.feriados.edit', $feriado) }}" class="btn btn-primary">
                        <i class="voyager-edit"></i> Editar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
