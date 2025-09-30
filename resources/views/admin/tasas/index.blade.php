@extends('voyager::master')

@section('content')
<div class="page-content browse container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Tasas IDTGB</h3>
                    <a href="{{ route('admin.tasas.create') }}" class="btn btn-success btn-add-new">
                        <i class="voyager-plus"></i> Nueva Tasa
                    </a>
                </div>
                <div class="panel-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Departamento</th>
                                <th>Parentesco</th>
                                <th>Tipo Transmisión</th>
                                <th>Tasa (%)</th>
                                <th>Vigente Desde</th>
                                <th>Vigente Hasta</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tasas as $t)
                            <tr>
                                <td>{{ $t->id }}</td>
                                <td>{{ $t->departamento->nombre }}</td>
                                <td>{{ $t->parentesco->nombre }}</td>
                                <td>{{ $t->tipoTransmision->nombre ?? '-' }}</td>
                                <td>{{ $t->tasa }}</td>
                                <td>{{ $t->vigente_desde->format('d/m/Y') }}</td>
                                <td>{{ $t->vigente_hasta?->format('d/m/Y') ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('admin.tasas.edit', $t) }}" class="btn btn-sm btn-primary">
                                        <i class="voyager-edit"></i> Editar
                                    </a>
                                    <form action="{{ route('admin.tasas.destroy', $t) }}" method="POST" style="display:inline;">
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
                    {{ $tasas->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@stop
