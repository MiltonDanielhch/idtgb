@extends('voyager::master')

@section('content')
<div class="page-content browse container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Parentescos</h3>
                    <a href="{{ route('admin.parentescos.create') }}" class="btn btn-success btn-add-new">
                        <i class="voyager-plus"></i> Nuevo
                    </a>
                </div>
                <div class="panel-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($parentescos as $p)
                            <tr>
                                <td>{{ $p->id }}</td>
                                <td>{{ $p->nombre }}</td>
                                <td>
                                    <a href="{{ route('admin.parentescos.edit', $p) }}" class="btn btn-sm btn-primary">
                                        <i class="voyager-edit"></i> Editar
                                    </a>
                                    <form action="{{ route('admin.parentescos.destroy', $p) }}" method="POST" style="display:inline;">
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
                    {{ $parentescos->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@stop
