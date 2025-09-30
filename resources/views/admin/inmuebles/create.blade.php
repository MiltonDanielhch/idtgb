@extends('voyager::master')

@section('content')
<div class="page-content edit container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Nuevo Inmueble</h3>
                </div>
                <form action="{{ route('admin.inmuebles.store') }}" method="POST">
                    @csrf
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Complemento</label>
                                <input type="text" name="complemento" class="form-control" maxlength="3">
                            </div>
                            <div class="col-md-3">
                                <label>Catástro *</label>
                                <input type="text" name="catastro" class="form-control" required maxlength="15">
                            </div>
                            <div class="col-md-3">
                                <label>Tipo Inmueble *</label>
                                <select name="tipo_inmueble_id" class="form-control select2" required>
                                    <option value="">Elija...</option>
                                    @foreach($tipos as $t)
                                        <option value="{{ $t->id }}">{{ $t->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Municipio</label>
                                <select name="municipio_id" class="form-control select2">
                                    <option value="">Ninguno</option>
                                    @foreach($municipios as $m)
                                        <option value="{{ $m->id }}">{{ $m->nombre }} ({{ $m->provincia->departamento->codigo }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row" style="margin-top:15px;">
                            <div class="col-md-4">
                                <label>Barrio / Comunidad</label>
                                <input type="text" name="barrio_comunidad" class="form-control" maxlength="100">
                            </div>
                            <div class="col-md-4">
                                <label>Dirección</label>
                                <input type="text" name="direccion" class="form-control" maxlength="200">
                            </div>
                            <div class="col-md-4">
                                <label>Matrícula RR</label>
                                <input type="text" name="matricula_rr" class="form-control" maxlength="20">
                            </div>
                        </div>
                        <div class="row" style="margin-top:15px;">
                            <div class="col-md-3">
                                <label>Superficie (m²)</label>
                                <input type="number" step="0.01" min="0" name="superficie_m2" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label>Valor Catastral *</label>
                                <input type="number" step="0.01" min="0" name="valor_catastral" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label>Es Vivienda Única</label>
                                <select name="es_vivienda_unica_familiar" class="form-control">
                                    <option value="0">No</option>
                                    <option value="1">Sí</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Estado</label>
                                <select name="estado_inmueble" class="form-control">
                                    <option value="Activo">Activo</option>
                                    <option value="Transferido">Transferido</option>
                                    <option value="Baja">Baja</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <a href="{{ route('admin.inmuebles.index') }}" class="btn btn-default">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop
