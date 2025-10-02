@extends('voyager::master')

@section('page_title', ($parentesco->exists ?? false) ? 'Editar Parentesco' : 'Agregar Parentesco')

@section('content')
<div class="page-content container-fluid">
    <form action="{{ ($parentesco->exists ?? false)
            ? route('admin.parentescos.update', $parentesco)
            : route('admin.parentescos.store') }}"
          method="POST" id="parentesco-form">
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
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="nombre">Nombre <span class="required">*</span></label>
                            <input type="text"
                                   name="nombre"
                                   id="nombre"
                                   class="form-control @error('nombre') is-invalid @enderror"
                                   maxlength="50"
                                   value="{{ old('nombre', optional($parentesco)->nombre) }}"
                                   required
                                   autofocus>
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Máximo 50 caracteres. El nombre debe ser único.
                            </small>
                        </div>
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

@section('javascript')
<script>
    $(document).ready(function () {
        // Validación frontend básica
        $('#parentesco-form').on('submit', function(e) {
            const nombre = $('#nombre').val().trim();

            if (!nombre) {
                e.preventDefault();
                alert('El nombre del parentesco es obligatorio');
                $('#nombre').focus();
                return false;
            }

            if (nombre.length > 50) {
                e.preventDefault();
                alert('El nombre no puede tener más de 50 caracteres');
                $('#nombre').focus();
                return false;
            }
        });

        // Auto-trim al perder foco
        $('#nombre').on('blur', function() {
            $(this).val($(this).val().trim());
        });
    });
</script>
@stop
