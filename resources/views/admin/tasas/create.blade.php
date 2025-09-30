@extends('voyager::master')

@section('content')
<div class="page-content edit container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Nueva Tasa</h3>
                </div>
                <form action="{{ route('admin.tasas.store') }}" method="POST">
                    @csrf
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Departamento *</label>
                                <select name="departamento_id" class="form-control select2" required>
                                    <option value="">Elija...</option>
                                    @foreach($departamentos as $d)
                                        <option value="{{ $d->id }}">{{ $d->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Parentesco *</label>
                                <select name="parentesco_id" class="form-control select2" required>
                                    <option value="">Elija...</option>
                                    @foreach($parentescos as $p)
                                        <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Tipo Transmisión</label>
                                <select name="tipo_transmision_id" class="form-control select2">
                                    <option value="">Ninguno</option>
                                    @foreach($tipos as $t)
                                        <option value="{{ $t->id }}">{{ $t->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Tasa (%)</label>
                                <input type="number" step="0.01" min="0" max="99.99" name="tasa" class="form-control" required>
                            </div>
                        </div>
                        <div class="row" style="margin-top:15px;">
                            <div class="col-md-6">
                                <label>Vigente Desde *</label>
                                <input type="date" name="vigente_desde" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label>Vigente Hasta</label>
                                <input type="date" name="vigente_hasta" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <a href="{{ route('admin.tasas.index') }}" class="btn btn-default">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop
