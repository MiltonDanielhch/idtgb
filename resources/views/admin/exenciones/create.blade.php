@extends('voyager::master')

@section('content')
<div class="page-content edit container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Nueva Exención</h3>
                </div>
                <form action="{{ route('admin.exenciones.store') }}" method="POST">
                    @csrf
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label>Nombre *</label>
                                <input type="text" name="nombre" class="form-control" required maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label>Tipo *</label>
                                <select name="tipo" class="form-control" required>
                                    <option value="porcentaje">Porcentaje</option>
                                    <option value="monto_fijo">Monto Fijo</option>
                                </select>
                            </div>
                        </div>
                        <div class="row" style="margin-top:15px;">
                            <div class="col-md-4">
                                <label>Valor *</label>
                                <input type="number" step="0.01" min="0" name="valor" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label>Monto Máximo</label>
                                <input type="number" step="0.01" min="0" name="monto_maximo" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label>Vigente Desde *</label>
                                <input type="date" name="vigente_desde" class="form-control" required>
                            </div>
                        </div>
                        <div class="row" style="margin-top:15px;">
                            <div class="col-md-12">
                                <label>Descripción *</label>
                                <textarea name="descripcion" class="form-control" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="row" style="margin-top:15px;">
                            <div class="col-md-12">
                                <label>Vigente Hasta</label>
                                <input type="date" name="vigente_hasta" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <a href="{{ route('admin.exenciones.index') }}" class="btn btn-default">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop
