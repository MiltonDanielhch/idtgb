@extends('voyager::master')

@section('content')
<div class="page-content browse container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Documentos del Trámite {{ $tramite->nro_tramite }}</h3>
                    <a href="{{ route('admin.tramites.documentos.create', $tramite) }}" class="btn btn-success btn-add-new">
                        <i class="voyager-plus"></i> Nuevo Documento
                    </a>
                </div>
                <div class="panel-body">
                    @if($documentos->isEmpty())
                        <p class="text-center">No hay documentos registrados.</p>
                    @else
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Persona</th>
                                    <th>Versión</th>
                                    <th>Vigente</th>
                                    <th>Hash SHA-256</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($documentos as $d)
                                <tr>
                                    <td>{{ $d->tipo_doc }}</td>
                                    <td>{{ optional($d->persona)->first_name.' '.optional($d->persona)->paternal_surname ?? '-' }}</td>
                                    <td>{{ $d->version }}</td>
                                    <td>
                                        <span class="label label-{{ $d->vigente ? 'success' : 'default' }}">
                                            {{ $d->vigente ? 'Sí' : 'No' }}
                                        </span>
                                    </td>
                                    <td><small>{{ substr($d->hash_sha256, 0, 16) }}...</small></td>
                                    <td>
                                        <a href="{{ route('admin.tramites.documentos.download', [$tramite, $d]) }}" class="btn btn-xs btn-info" target="_blank">
                                            <i class="voyager-download"></i> Descargar
                                        </a>
                                        <form action="{{ route('admin.tramites.documentos.destroy', [$tramite, $d]) }}" method="POST" style="display:inline;">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-xs btn-danger" onclick="return confirm('¿Borrar?')">
                                                <i class="voyager-trash"></i> Borrar
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
