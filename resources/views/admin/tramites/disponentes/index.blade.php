@extends('voyager::master')

@section('content')
<div class="page-content browse container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Disponentes del Trámite {{ $tramite->nro_tramite }}</h3>
                    <a href="{{ route('admin.tramites.disponentes.create', $tramite) }}" class="btn btn-success btn-add-new">
                        <i class="voyager-plus"></i> Agregar Disponente
                    </a>
                </div>
                <div class="panel-body">
                    @if($disponentes->isEmpty())
                        <p class="text-center">No hay disponentes registrados.</p>
                    @else
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Persona</th>
                                    <th>Tipo</th>
                                    <th>Fallecimiento</th>
                                    <th>Discapacidad</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($disponentes as $d)
                                <tr>
                                    <td>{{ $d->persona->first_name.' '.$d->persona->paternal_surname }}</td>
                                    <td><span class="label label-primary">{{ $d->tipo }}</span></td>
                                    <td>{{ $d->fecha_fallecimiento ? $d->fecha_fallecimiento->format('d/m/Y') : '-' }}</td>
                                    <td>
                                        @if($d->es_discapacitado)
                                            <span class="label label-warning">Sí</span>
                                        @else
                                            <span class="label label-default">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.tramites.disponentes.destroy', [$tramite, $d]) }}" method="POST" style="display:inline;">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger" onclick="return confirm('¿Quitar?')">
                                                <i class="voyager-trash"></i> Quitar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@stop
