@extends('voyager::master')

@section('content')
<div class="page-content browse container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Inmuebles IDTGB</h3>
                    <a href="{{ route('admin.inmuebles.create') }}" class="btn btn-success btn-add-new">
                        <i class="voyager-plus"></i> Nuevo Inmueble
                    </a>
                </div>
                <div class="panel-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Catástro</th>
                                <th>Tipo</th>
                                <th>Municipio</th>
                                <th>Superficie (m²)</th>
                                <th>Valor Catastral</th>
                                <th>Viv. Única</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inmuebles as $inmueble)
                            <tr>
                                <td>{{ $inmueble->catastro }}</td>
                                <td>{{ $inmueble->tipoInmueble->nombre }}</td>
                                <td>{{ $inmueble->municipio->nombre ?? '-' }}</td>
                                <td>{{ number_format($inmueble->superficie_m2, 2) }}</td>
                                <td>{{ number_format($inmueble->valor_catastral, 2) }}</td>
                                <td>{{ $inmueble->es_vivienda_unica_familiar ? 'Sí' : 'No' }}</td>
                                <td>
                                    <span class="label label-{{ $inmueble->estado_inmueble == 'Activo' ? 'success' : ($inmueble->estado_inmueble == 'Transferido' ? 'warning' : 'danger') }}">
                                        {{ $inmueble->estado_inmueble }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.inmuebles.edit', $inmueble) }}" class="btn btn-sm btn-primary">
                                        <i class="voyager-edit"></i> Editar
                                    </a>
                                    <form action="{{ route('admin.inmuebles.destroy', $inmueble) }}" method="POST" style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-danger" onclick="return confirm('¿Borrar?')">
                                            <i class="voyager-trash"></i> Borrar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $inmuebles->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@stop
