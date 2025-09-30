@extends('voyager::master')

@section('content')
<div class="page-content edit container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Nuevo Documento del Trámite {{ $tramite->nro_tramite }}</h3>
                </div>
                <form action="{{ route('admin.tramites.documentos.store', $tramite) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label>Tipo de Documento *</label>
                                <select name="tipo_doc" class="form-control" required>
                                    @foreach($tipos as $t)
                                        <option value="{{ $t }}">{{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Persona (opcional)</label>
                                <select name="persona_id" class="form-control select2">
                                    <option value="">Ninguno</option>
                                    @foreach($personas as $p)
                                        <option value="{{ $p->id }}">{{ $p->first_name.' '.$p->paternal_surname }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Versión</label>
                                <input type="number" name="version" class="form-control" value="1" min="1">
                            </div>
                        </div>
                        <div class="row" style="margin-top:15px;">
                            <div class="col-md-6">
                                <label>Documento (pdf/jpg/png ≤ 5 MB) *</label>
                                <input type="file" name="documento" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label>
                                    <input type="checkbox" name="vigente" value="1" checked>
                                    ¿Vigente?
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer text-right">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <a href="{{ route('admin.tramites.documentos.index', $tramite) }}" class="btn btn-default">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop
