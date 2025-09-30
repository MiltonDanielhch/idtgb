@extends('voyager::master')

@section('content')
<div class="page-content edit container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Nuevo Avalúo</h3>
                </div>
                <form action="{{ route('admin.avaluos.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label>Inmueble *</label>
                                <select name="inmueble_id" class="form-control select2" required>
                                    <option value="">Elija...</option>
                                    @foreach($inmuebles as $i)
                                        <option value="{{ $i->id }}">{{ $i->catastro }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Tipo Avalúo *</label>
                                <select name="tipo_avaluo" class="form-control" required>
                                    <option value="Fiscal">Fiscal</option>
                                    <option value="Comercial">Comercial</option>
                                    <option value="Pericial">Pericial</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Fecha Avalúo *</label>
                                <input type="date" name="fecha_avaluo" class="form-control" required>
                            </div>
                        </div>
                        <div class="row" style="margin-top:15px;">
                            <div class="col-md-4">
                                <label>Valor (Bs) *</label>
                                <input type="number" step="0.01" min="0" name="valor" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label>Perito</label>
                                <select name="perito_id" class="form-control select2">
                                    <option value="">Ninguno</option>
                                    @foreach($peritos as $p)
                                        <option value="{{ $p->id }}">{{ $p->first_name.' '.$p->paternal_surname }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Estado</label>
                                <select name="estado" class="form-control">
                                    <option value="Vigente">Vigente</option>
                                    <option value="Caducado">Caducado</option>
                                </select>
                            </div>
                        </div>
                        <div class="row" style="margin-top:15px;">
                            <div class="col-md-12">
                                <label>Documento (pdf/jpg/png ≤ 5 MB)</label>
                                <input type="file" name="documento" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <a href="{{ route('admin.avaluos.index') }}" class="btn btn-default">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop
