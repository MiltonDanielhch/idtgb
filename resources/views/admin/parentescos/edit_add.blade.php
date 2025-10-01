@extends('voyager::master')

@section('page_title', ($parentesco->exists ?? false) ? 'Editar Parentesco' : 'Agregar Parentesco')

@section('content')
<div class="page-content container-fluid">
    <form action="{{ ($parentesco->exists ?? false)
            ? route('admin.parentescos.update',    $parentesco)
            : route('admin.parentescos.store') }}"
          method="POST">
        @csrf
        @if($parentesco->exists ?? false) @method('PUT') @endif

        <div class="panel panel-bordered panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="voyager-tag"></i>
                    {{ ($parentesco->exists ?? false) ? 'Editar' : 'Agregar' }} Parentesco
                </h3>
            </div>

            <div class="panel-body">
                <div class="row">
                    <div class="col-md-12">
                        <label>Nombre <span class="required">*</span></label>
                        <input type="text"
                               name="nombre"
                               class="form-control"
                               maxlength="50"
                               value="{{ old('nombre', optional($parentesco)->nombre) }}"
                               required>
                    </div>
                </div>
            </div>

            <div class="panel-footer text-right">
                <a href="{{ route('admin.parentescos.index') }}" class="btn btn-default">
                    <i class="voyager-angle-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="voyager-check"></i> {{ ($parentesco->exists ?? false) ? 'Actualizar' : 'Guardar' }}
                </button>
            </div>
        </div>
    </form>
</div>
@stop
