@extends('voyager::master')

@section('content')
<div class="page-content edit container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Nuevo Trámite IDTGB</h3>
                </div>
                <form action="{{ route('admin.tramites.store') }}" method="POST">
                    @csrf
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Nro Trámite *</label>
                                <input type="text" name="nro_tramite" class="form-control" required maxlength="15">
                            </div>
                            <div class="col-md-3">
                                <label>Fecha Presentación *</label>
                                <input type="date" name="fecha_presentacion" class="form-control" required value="{{ today()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-3">
                                <label>Tipo Transmisión *</label>
                                <select name="tipo_transmision_id" class="form-control select2" required>
                                    <option value="">Elija...</option>
                                    @foreach($tipos as $t)
                                        <option value="{{ $t->id }}">{{ $t->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Inmueble *</label>
                                <select name="inmueble_id" class="form-control select2" required>
                                    <option value="">Elija...</option>
                                    @foreach($inmuebles as $i)
                                        <option value="{{ $i->id }}">{{ $i->catastro }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row" style="margin-top:15px;">
                            <div class="col-md-3">
                                <label>Valor Declarado (Bs) *</label>
                                <input type="number" step="0.01" min="0" name="valor_declarado" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label>Base Imponible (Bs) *</label>
                                <input type="number" step="0.01" min="0" name="base_imponible" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label>Fecha Transmisión *</label>
                                <input type="date" name="fecha_transmision" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label>Estado</label>
                                <select name="estado" class="form-control">
                                    <option value="Borrador">Borrador</option>
                                    <option value="Pagado">Pagado</option>
                                    <option value="Observado">Observado</option>
                                    <option value="Anulado">Anulado</option>
                                    <option value="Finalizado">Finalizado</option>
                                </select>
                            </div>
                        </div>
                        <div class="row" style="margin-top:15px;">
                            <div class="col-md-12">
                                <label>Observaciones</label>
                                <textarea name="observaciones" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <a href="{{ route('admin.tramites.index') }}" class="btn btn-default">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop
