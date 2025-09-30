@extends('voyager::master')

@section('content')
<div class="page-content browse container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">UFVs - Unidad de Fomento a la Vivienda</h3>
                    <a href="{{ route('admin.ufvs.create') }}" class="btn btn-success btn-add-new">
                        <i class="voyager-plus"></i> Nueva UFV
                    </a>
                </div>
                <div class="panel-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Valor</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ufvs as $u)
                            <tr>
                                <td>{{ $u->fecha->format('d/m/Y') }}</td>
                                <td>{{ number_format($u->valor, 5) }}</td>
                                <td>
                                    <a href="{{ route('admin.ufvs.edit', $u) }}" class="btn btn-sm btn-primary">
                                        <i class="voyager-edit"></i> Editar
                                    </a>
                                    <form action="{{ route('admin.ufvs.destroy', $u) }}" method="POST" style="display:inline;">
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
                    {{ $ufvs->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@stop
