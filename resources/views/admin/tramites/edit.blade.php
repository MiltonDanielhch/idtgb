@extends('voyager::master')

@section('content')
<div class="page-content edit container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Editar Trámite IDTGB</h3>
                </div>
                <form action="{{ route('admin.tramites.update', $tramite) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Nro Trámite *</label>
                                <input type="text" name="nro_tramite" class="form-control" value="{{ old('nro_tramite', $tramite->nro_tramite) }}" required maxlength="15">
                            </div>
                            <div class="col-md-3">
                                <label>Fecha Presentación *</label>
                                <input type="date" name="fecha_presentacion" class="form-control" value="{{ old('fecha_presentacion', $tramite->fecha_presentacion->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label>Tipo Transmisión *</label>
                                <select name="tipo_transmision_id" class="form-control select2" required>
                                    @foreach($tipos as $t)
                                        <option value="{{ $t->id }}" {{ $t->id == old('tipo_transmision_id', $tramite->tipo_transmision_id) ? 'selected' : '' }}>{{ $t->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Inmueble *</label>
                                <select name="inmueble_id" class="form-control select2" required>
                                    @foreach($inmuebles as $i)
                                        <option value="{{ $i->id }}" {{ $i->id == old('inmueble_id', $tramite->inmueble_id) ? 'selected' : '' }}>{{ $i->catastro }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row" style="margin-top:15px;">
                            <div class="col-md-3">
                                <label>Valor Declarado (Bs) *</label>
                                <input type="number" step="0.01" min="0" name="valor_declarado" class="form-control" value="{{ old('valor_declarado', $tramite->valor_declarado) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label>Base Imponible (Bs) *</label>
                                <input type="number" step="0.01" min="0" name="base_imponible" class="form-control" value="{{ old('base_imponible', $tramite->base_imponible) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label>Fecha Transmisión *</label>
                                <input type="date" name="fecha_transmision" class="form-control" value="{{ old('fecha_transmision', $tramite->fecha_transmision->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label>Estado</label>
                                <select name="estado" class="form-control">
                                    <option value="Borrador" {{ old('estado', $tramite->estado) == 'Borrador' ? 'selected' : '' }}>Borrador</option>
                                    <option value="Pagado" {{ old('estado', $tramite->estado) == 'Pagado' ? 'selected' : '' }}>Pagado</option>
                                    <option value="Observado" {{ old('estado', $tramite->estado) == 'Observado' ? 'selected' : '' }}>Observado</option>
                                    <option value="Anulado" {{ old('estado', $tramite->estado) == 'Anulado' ? 'selected' : '' }}>Anulado</option>
                                    <option value="Finalizado" {{ old('estado', $tramite->estado) == 'Finalizado' ? 'selected' : '' }}>Finalizado</option>
                                </select>
                            </div>
                        </div>
                        <div class="row" style="margin-top:15px;">
                            <div class="col-md-12">
                                <label>Observaciones</label>
                                <textarea name="observaciones" class="form-control" rows="3">{{ old('observaciones', $tramite->observaciones) }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                        <a href="{{ route('admin.tramites.index') }}" class="btn btn-default">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop
