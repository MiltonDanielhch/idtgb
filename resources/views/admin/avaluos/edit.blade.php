@extends('voyager::master')

@section('content')
<div class="page-content edit container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Editar Avalúo</h3>
                </div>
                <form action="{{ route('admin.avaluos.update', $avaluo) }}" method="POST" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label>Inmueble *</label>
                                <select name="inmueble_id" class="form-control select2" required>
                                    @foreach($inmuebles as $i)
                                        <option value="{{ $i->id }}" {{ $i->id == old('inmueble_id', $avaluo->inmueble_id) ? 'selected' : '' }}>{{ $i->catastro }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Tipo Avalúo *</label>
                                <select name="tipo_avaluo" class="form-control" required>
                                    <option value="Fiscal" {{ old('tipo_avaluo', $avaluo->tipo_avaluo) == 'Fiscal' ? 'selected' : '' }}>Fiscal</option>
                                    <option value="Comercial" {{ old('tipo_avaluo', $avaluo->tipo_avaluo) == 'Comercial' ? 'selected' : '' }}>Comercial</option>
                                    <option value="Pericial" {{ old('tipo_avaluo', $avaluo->tipo_avaluo) == 'Pericial' ? 'selected' : '' }}>Pericial</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Fecha Avalúo *</label>
                                <input type="date" name="fecha_avaluo" class="form-control" value="{{ old('fecha_avaluo', $avaluo->fecha_avaluo->format('Y-m-d')) }}" required>
                            </div>
                        </div>
                        <div class="row" style="margin-top:15px;">
                            <div class="col-md-4">
                                <label>Valor (Bs) *</label>
                                <input type="number" step="0.01" min="0" name="valor" class="form-control" value="{{ old('valor', $avaluo->valor) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label>Perito</label>
                                <select name="perito_id" class="form-control select2">
                                    <option value="">Ninguno</option>
                                    @foreach($peritos as $p)
                                        <option value="{{ $p->id }}" {{ $p->id == old('perito_id', $avaluo->perito_id) ? 'selected' : '' }}>{{ $p->first_name.' '.$p->paternal_surname }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Estado</label>
                                <select name="estado" class="form-control">
                                    <option value="Vigente" {{ old('estado', $avaluo->estado) == 'Vigente' ? 'selected' : '' }}>Vigente</option>
                                    <option value="Caducado" {{ old('estado', $avaluo->estado) == 'Caducado' ? 'selected' : '' }}>Caducado</option>
                                </select>
                            </div>
                        </div>
                        <div class="row" style="margin-top:15px;">
                            <div class="col-md-12">
                                <label>Documento (pdf/jpg/png ≤ 5 MB)</label>
                                <input type="file" name="documento" class="form-control">
                                @if($avaluo->documento_path)
                                    <p class="help-block">Archivo actual: <a href="{{ route('admin.avaluos.download', $avaluo) }}" target="_blank">Descargar</a></p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                        <a href="{{ route('admin.avaluos.index') }}" class="btn btn-default">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop
