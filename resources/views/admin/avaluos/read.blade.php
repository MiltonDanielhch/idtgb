@extends('voyager::master')

@section('page_title', 'Ver Avalúo')

@section('content')
<div class="page-content container-fluid">
    <div class="panel panel-bordered panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">
                <i class="fa-solid fa-file-invoice-dollar"></i> Ver Avalúo: {{ $avaluo->tipo_avaluo }}
            </h3>
        </div>

        <div class="panel-body">
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th style="width: 200px;">ID</th>
                                <td>{{ $avaluo->id }}</td>
                            </tr>
                            <tr>
                                <th>Inmueble (Catástro)</th>
                                <td>
                                    <a href="{{ route('admin.inmuebles.show', $avaluo->inmueble) }}" class="btn btn-xs btn-info">
                                        <i class="fa-solid fa-home"></i> {{ $avaluo->inmueble->catastro }}
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <th>Tipo de Avalúo</th>
                                <td><strong>{{ $avaluo->tipo_avaluo }}</strong></td>
                            </tr>
                            <tr>
                                <th>Fecha del Avalúo</th>
                                <td>{{ $avaluo->fecha_avaluo->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <th>Valor (Bs)</th>
                                <td>
                                    <span class="badge badge-primary" style="font-size: 1.1em;">
                                        Bs. {{ number_format($avaluo->valor, 2) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Perito</th>
                                <td>
                                    @if($avaluo->perito)
                                        {{ $avaluo->perito->first_name }} {{ $avaluo->perito->paternal_surname }}
                                    @else
                                        <span class="text-muted">Sin perito asignado</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Estado</th>
                                <td>
                                    <span class="badge badge-{{ $avaluo->estado == 'Vigente' ? 'success' : 'danger' }}">
                                        {{ $avaluo->estado }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Documento</th>
                                <td>
                                    @if($avaluo->documento_path)
                                        <a href="{{ route('admin.avaluos.download', $avaluo) }}" class="btn btn-xs btn-info" target="_blank">
                                            <i class="voyager-download"></i> Descargar archivo
                                        </a>
                                    @else
                                        <span class="text-muted">Sin archivo adjunto</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Creado</th>
                                <td>{{ $avaluo->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th>Actualizado</th>
                                <td>{{ $avaluo->updated_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="panel-footer text-right">
            <a href="{{ route('admin.avaluos.index') }}" class="btn btn-default">
                <i class="voyager-angle-left"></i> Volver
            </a>

            @can('update', $avaluo)
                <a href="{{ route('admin.avaluos.edit', $avaluo) }}" class="btn btn-primary">
                    <i class="voyager-edit"></i> Editar
                </a>
            @endcan
        </div>
    </div>
</div>
@stop
