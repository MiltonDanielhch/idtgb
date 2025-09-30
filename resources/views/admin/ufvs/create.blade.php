@extends('voyager::master')

@section('content')
<div class="page-content edit container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Nueva UFV</h3>
                </div>
                <form action="{{ route('admin.ufvs.store') }}" method="POST">
                    @csrf
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label>Fecha *</label>
                                <input type="date" name="fecha" class="form-control" required value="{{ old('fecha', today()->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6">
                                <label>Valor *</label>
                                <input type="number" step="0.00001" min="0" max="999.99999" name="valor" class="form-control" required value="{{ old('valor') }}">
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer text-right">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <a href="{{ route('admin.ufvs.index') }}" class="btn btn-default">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop
