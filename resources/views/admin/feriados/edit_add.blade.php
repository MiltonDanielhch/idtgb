@extends('voyager::master')

@section('page_title', ($feriado->exists ?? false) ? 'Editar Feriado' : 'Agregar Feriado')

@section('content')
<div class="page-content container-fluid">
    {{-- Mostrar mensajes de éxito/error --}}
    @if(session('message'))
        <div class="alert alert-{{ session('alert-type', 'info') }} alert-dismissible auto-dismiss">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            {{ session('message') }}
        </div>
    @endif

    <form action="{{ ($feriado->exists ?? false)
            ? route('admin.feriados.update', $feriado)
            : route('admin.feriados.store') }}"
          method="POST" id="feriado-form">
        @csrf
        @if($feriado->exists ?? false) @method('PUT') @endif

        <div class="panel panel-bordered panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="voyager-calendar"></i>
                    {{ ($feriado->exists ?? false) ? 'Editar' : 'Agregar' }} Feriado
                </h3>
            </div>

            <div class="panel-body">
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible auto-dismiss">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <strong>Por favor corrige los siguientes errores:</strong>
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="fecha">Fecha <span class="required">*</span></label>
                            <input type="date"
                                   name="fecha"
                                   id="fecha"
                                   class="form-control @error('fecha') is-invalid @enderror"
                                   value="{{ old('fecha', optional($feriado)->fecha ? optional($feriado)->fecha->format('Y-m-d') : '') }}"
                                   required>
                            @error('fecha')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tipo">Tipo <span class="required">*</span></label>
                            <select name="tipo" id="tipo" class="form-control @error('tipo') is-invalid @enderror" required>
                                <option value="">-- Seleccione --</option>
                                <option value="Nacional" {{ old('tipo', optional($feriado)->tipo) === 'Nacional' ? 'selected' : '' }}>Nacional</option>
                                <option value="Departamental" {{ old('tipo', optional($feriado)->tipo) === 'Departamental' ? 'selected' : '' }}>Departamental</option>
                                <option value="Municipal" {{ old('tipo', optional($feriado)->tipo) === 'Municipal' ? 'selected' : '' }}>Municipal</option>
                            </select>
                            @error('tipo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="nombre">Nombre <span class="required">*</span></label>
                            <input type="text"
                                   name="nombre"
                                   id="nombre"
                                   class="form-control @error('nombre') is-invalid @enderror"
                                   placeholder="Ej: Año Nuevo, Día del Trabajador, etc."
                                   maxlength="100"
                                   value="{{ old('nombre', optional($feriado)->nombre) }}"
                                   required>
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="departamento_id">Departamento</label>
                            <select name="departamento_id" id="departamento_id" class="form-control">
                                <option value="">-- Nacional (Todos los departamentos) --</option>
                                @foreach($departamentos as $departamento)
                                    <option value="{{ $departamento->id }}" {{ old('departamento_id', optional($feriado)->departamento_id) == $departamento->id ? 'selected' : '' }}>
                                        {{ $departamento->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                Dejar vacío para feriados nacionales.
                            </small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="activo">Estado</label>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="activo" value="1" {{ old('activo', optional($feriado)->activo ?? true) ? 'checked' : '' }}>
                                    Activo
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Los feriados inactivos no se consideran en el cálculo de días hábiles.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel-footer text-right">
                <a href="{{ route('admin.feriados.index') }}" class="btn btn-default">
                    <i class="voyager-angle-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="voyager-check"></i> {{ ($feriado->exists ?? false) ? 'Actualizar' : 'Guardar' }}
                </button>
            </div>
        </div>
    </form>
</div>
@stop

@section('javascript')
<script>
    $(document).ready(function () {
        // ========== AUTO-DISMISS ALERTS ==========
        setTimeout(function() {
            $('.auto-dismiss').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 5000);

        $('.auto-dismiss .close').click(function(e) {
            e.preventDefault();
            $(this).closest('.alert').fadeOut('slow', function() {
                $(this).remove();
            });
        });

        // ========== FORM VALIDATION ==========
        $('#nombre').on('blur', function() {
            $(this).val($(this).val().trim());
        });

        $('#feriado-form').on('submit', function() {
            const nombre = $('#nombre').val().trim();
            $('#nombre').val(nombre);

            if (!nombre) {
                alert('El nombre del feriado es obligatorio');
                $('#nombre').focus();
                return false;
            }
        });
    });
</script>
@stop
