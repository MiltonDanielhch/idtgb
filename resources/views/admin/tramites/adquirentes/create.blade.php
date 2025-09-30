@extends('voyager::master')

@section('content')
<div class="page-content edit container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Agregar Adquirente al Trámite {{ $tramite->nro_tramite }}</h3>
                </div>
                <form action="{{ route('admin.tramites.adquirentes.store', $tramite) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label>Persona *</label>
                                <select name="persona_id" class="form-control select2" required>
                                    <option value="">Elija...</option>
                                    @foreach($personas as $p)
                                        <option value="{{ $p->id }}">{{ $p->first_name.' '.$p->paternal_surname }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Parentesco *</label>
                                <select name="parentesco_id" class="form-control select2" required>
                                    <option value="">Elija...</option>
                                    @foreach($parentescos as $pa)
                                        <option value="{{ $pa->id }}">{{ $pa->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Porcentaje *</label>
                                <input type="number" step="0.01" min="0.01" max="100" name="porcentaje" class="form-control" required>
                            </div>
                        </div>
                        <div class="row" style="margin-top:15px;">
                            <div class="col-md-6">
                                <label>¿Beneficiario de Exención?</label>
                                <select name="es_beneficiario_exencion" class="form-control">
                                    <option value="0">No</option>
                                    <option value="1">Sí</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label>Documento Sustento (pdf/jpg/png ≤ 2 MB)</label>
                                <input type="file" name="documento_sustento_exencion" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer text-right">
                        <button type="submit" class="btn btn-primary">Agregar</button>
                        <a href="{{ route('admin.tramites.adquirentes.index', $tramite) }}" class="btn btn-default">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop
