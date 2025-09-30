@extends('voyager::master')

@section('content')
<div class="page-content edit container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Editar Tasa</h3>
                </div>
                <form action="{{ route('admin.tasas.update', $tasa) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Departamento *</label>
                                <select name="departamento_id" class="form-control select2" required>
                                    @foreach($departamentos as $d)
                                        <option value="{{ $d->id }}" {{ $d->id == old('departamento_id', $tasa->departamento_id) ? 'selected' : '' }}>{{ $d->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Parentesco *</label>
                                <select name="parentesco_id" class="form-control select2" required>
                                    @foreach($parentescos as $p)
                                        <option value="{{ $p->id }}" {{ $p->id == old('parentesco_id', $tasa->parentesco_id) ? 'selected' : '' }}>{{ $p->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Tipo Transmisión</label>
                                <select name="tipo_transmision_id" class="form-control select2">
                                    <option value="">Ninguno</option>
                                    @foreach($tipos as $t)
                                        <option value="{{ $t->id }}" {{ $t->id == old('tipo_transmision_id', $tasa->tipo_transmision_id) ? 'selected' : '' }}>{{ $t->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Tasa (%)</label>
                                <input type="number" step="0.01" min="0" max="99.99" name="tasa" class="form-control" value="{{ old('tasa', $tasa->tasa) }}" required>
                            </div>
                        </div>
                        <div class="row" style="margin-top:15px;">
                            <div class="col-md-6">
                                <label>Vigente Desde *</label>
                                <input type="date" name="vigente_desde" class="form-control" value="{{ old('vigente_desde', $tasa->vigente_desde->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label>Vigente Hasta</label>
                                <input type="date" name="vigente_hasta" class="form-control" value="{{ old('vigente_hasta', optional($tasa->vigente_hasta)->format('Y-m-d')) }}">
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                        <a href="{{ route('admin.tasas.index') }}" class="btn btn-default">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop
