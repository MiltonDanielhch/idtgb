@extends('voyager::master')

@section('content')
<div class="page-content browse container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Exenciones IDTGB</h3>
                    <a href="{{ route('admin.exenciones.create') }}" class="btn btn-success btn-add-new">
                        <i class="voyager-plus"></i> Nueva Exención
                    </a>
                </div>
                <div class="panel-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Valor</th>
                                <th>Monto Máx.</th>
                                <th>Vigente Desde</th>
                                <th>Vigente Hasta</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($exenciones as $e)
                            <tr>
                                <td>{{ $e->id }}</td>
                                <td>{{ $e->nombre }}</td>
                                <td>{{ ucfirst($e->tipo) }}</td>
                                <td>{{ number_format($e->valor, 2) }}</td>
                                <td>{{ $e->monto_maximo ? number_format($e->monto_maximo, 2) : '-' }}</td>
                                <td>{{ $e->vigente_desde->format('d/m/Y') }}</td>
                                <td>{{ $e->vigente_hasta?->format('d/m/Y') ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('admin.exenciones.edit', $e) }}" class="btn btn-sm btn-primary">
                                        <i class="voyager-edit"></i> Editar
                                    </a>
                                    <form action="{{ route('admin.exenciones.destroy', $e) }}" method="POST" style="display:inline;">
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
                    {{ $exenciones->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@stop
