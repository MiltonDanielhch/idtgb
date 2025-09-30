@extends('voyager::master')

@section('content')
<div class="page-content browse container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Avalúos IDTGB</h3>
                    <a href="{{ route('admin.avaluos.create') }}" class="btn btn-success btn-add-new">
                        <i class="voyager-plus"></i> Nuevo Avalúo
                    </a>
                </div>
                <div class="panel-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Inmueble (Catástro)</th>
                                <th>Tipo</th>
                                <th>Fecha</th>
                                <th>Valor (Bs)</th>
                                <th>Perito</th>
                                <th>Doc</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($avaluos as $a)
                            <tr>
                                <td>{{ $a->inmueble->catastro }}</td>
                                <td>{{ $a->tipo_avaluo }}</td>
                                <td>{{ $a->fecha_avaluo->format('d/m/Y') }}</td>
                                <td>{{ number_format($a->valor, 2) }}</td>
                                <td>{{ optional($a->perito)->first_name.' '.optional($a->perito)->paternal_surname ?? '-' }}</td>
                                <td>
                                    @if($a->documento_path)
                                        <a href="{{ route('admin.avaluos.download', $a) }}" class="btn btn-xs btn-info" target="_blank">
                                            <i class="voyager-download"></i>
                                        </a>
                                    @else
                                        <span class="label label-default">Sin archivo</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="label label-{{ $a->estado == 'Vigente' ? 'success' : 'danger' }}">
                                        {{ $a->estado }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.avaluos.edit', $a) }}" class="btn btn-sm btn-primary">
                                        <i class="voyager-edit"></i> Editar
                                    </a>
                                    <form action="{{ route('admin.avaluos.destroy', $a) }}" method="POST" style="display:inline;">
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
                    {{ $avaluos->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@stop
