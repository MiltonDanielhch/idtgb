@extends('voyager::master')

@section('content')
<div class="page-content edit container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Editar Parentesco</h3>
                </div>
                <form action="{{ route('admin.parentescos.update', $parentesco) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="panel-body">
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $parentesco->nombre) }}" required maxlength="50">
                        </div>
                    </div>
                    <div class="panel-footer">
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                        <a href="{{ route('admin.parentescos.index') }}" class="btn btn-default">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop
