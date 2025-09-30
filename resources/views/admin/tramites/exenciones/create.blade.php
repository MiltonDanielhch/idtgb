@extends('voyager::master')

@section('content')
<div class="page-content edit container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Aplicar Exención al Trámite {{ $tramite->nro_tramite }}</h3>
                </div>
                <form action="{{ route('admin.tramites.exenciones.store', $tramite) }}" method="POST">
                    @csrf
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-8">
                                <label>Exención *</label>
                                <select name="exencion_id" class="form-control select2" required>
                                    <option value="">Elija...</option>
                                    @foreach($exenciones as $e)
                                        <option value="{{ $e->id }}">{{ $e->nombre }} ({{ ucfirst($e->tipo) }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Monto a Aplicar *</label>
                                <input type="number" step="0.01" min="0" name="monto_aplicado" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer text-right">
                        <button type="submit" class="btn btn-primary">Aplicar</button>
                        <a href="{{ route('admin.tramites.exenciones.index', $tramite) }}" class="btn btn-default">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop
