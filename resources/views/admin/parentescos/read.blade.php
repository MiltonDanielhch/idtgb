@extends('voyager::master')

@section('page_title', 'Ver Parentesco')

@section('content')
<div class="page-content container-fluid">
    <div class="panel panel-bordered panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">
                <i class="voyager-eye"></i> Ver Parentesco: {{ $parentesco->nombre }}
            </h3>
        </div>

        <div class="panel-body">
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th style="width: 200px;">ID</th>
                                <td>{{ $parentesco->id }}</td>
                            </tr>
                            <tr>
                                <th>Nombre</th>
                                <td>{{ $parentesco->nombre }}</td>
                            </tr>
                            <tr>
                                <th>Creado</th>
                                <td>{{ optional($parentesco->created_at)->format('d/m/Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th>Actualizado</th>
                                <td>{{ optional($parentesco->updated_at)->format('d/m/Y H:i') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="panel-footer text-right">
            {{-- Botón para volver al listado --}}
            <a href="{{ route('admin.parentescos.index') }}" class="btn btn-default">
                <i class="voyager-angle-left"></i> Volver
            </a>

            {{-- Botón de edición con control de permisos usando la Policy --}}
            @can('update', $parentesco)
                <a href="{{ route('admin.parentescos.edit', $parentesco) }}" class="btn btn-primary">
                    <i class="voyager-edit"></i> Editar
                </a>
            @endcan
        </div>
    </div>
</div>
@stop
