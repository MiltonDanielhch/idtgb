@extends('voyager::master')

@section('page_title', ($exencion->exists ?? false) ? 'Editar Exención' : 'Agregar Exención')

@section('content')
<div class="page-content container-fluid">
    @if(session('message'))
        <div class="alert alert-{{ session('alert-type', 'info') }} alert-dismissible auto-dismiss">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            {{ session('message') }}
        </div>
    @endif

    <form action="{{ ($exencion->exists ?? false)
            ? route('admin.exenciones.update', $exencion)
            : route('admin.exenciones.store') }}"
          method="POST" id="exencion-form">
        @csrf
        @if($exencion->exists ?? false) @method('PUT') @endif

        <div class="panel panel-bordered panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="voyager-gift"></i>
                    {{ ($exencion->exists ?? false) ? 'Editar' : 'Agregar' }} Exención
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
                    {{-- Nombre --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nombre">Nombre <span class="required">*</span></label>
                            <input type="text"
                                   name="nombre"
                                   id="nombre"
                                   class="form-control @error('nombre') is-invalid @enderror"
                                   placeholder="Ej: Cónyuge, Discapacidad, etc."
                                   maxlength="100"
                                   value="{{ old('nombre', optional($exencion)->nombre) }}"
                                   required
                                   autofocus>
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Máximo 100 caracteres. Debe ser único.</small>
                        </div>
                    </div>

                    {{-- Tipo --}}
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="tipo">Tipo <span class="required">*</span></label>
                            <select name="tipo" id="tipo" class="form-control @error('tipo') is-invalid @enderror" required>
                                <option value="">-- Seleccione --</option>
                                <option value="porcentaje" {{ old('tipo', optional($exencion)->tipo) == 'porcentaje' ? 'selected' : '' }}>Porcentaje</option>
                                <option value="monto_fijo" {{ old('tipo', optional($exencion)->tipo) == 'monto_fijo' ? 'selected' : '' }}>Monto Fijo</option>
                            </select>
                            @error('tipo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Valor --}}
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="valor">Valor <span class="required">*</span></label>
                            <input type="number"
                                   step="0.01"
                                   name="valor"
                                   id="valor"
                                   class="form-control @error('valor') is-invalid @enderror"
                                   placeholder="0.00"
                                   value="{{ old('valor', optional($exencion)->valor) }}"
                                   required>
                            @error('valor')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Ej: 50.00 (si es %, 50 = 50%)</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- Monto Máximo --}}
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="monto_maximo">Monto Máximo (opcional)</label>
                            <input type="number"
                                   step="0.01"
                                   name="monto_maximo"
                                   id="monto_maximo"
                                   class="form-control @error('monto_maximo') is-invalid @enderror"
                                   placeholder="0.00"
                                   value="{{ old('monto_maximo', optional($exencion)->monto_maximo) }}">
                            @error('monto_maximo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Tope en Bs. Dejar vacío si no aplica.</small>
                        </div>
                    </div>

                    {{-- Vigente Desde --}}
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="vigente_desde">Vigente Desde <span class="required">*</span></label>
                            <input type="date"
                                   name="vigente_desde"
                                   id="vigente_desde"
                                   class="form-control @error('vigente_desde') is-invalid @enderror"
                                   value="{{ old('vigente_desde', optional($exencion)->vigente_desde?->format('Y-m-d')) }}"
                                   required>
                            @error('vigente_desde')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Vigente Hasta --}}
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="vigente_hasta">Vigente Hasta (opcional)</label>
                            <input type="date"
                                   name="vigente_hasta"
                                   id="vigente_hasta"
                                   class="form-control @error('vigente_hasta') is-invalid @enderror"
                                   value="{{ old('vigente_hasta', optional($exencion)->vigente_hasta?->format('Y-m-d')) }}">
                            @error('vigente_hasta')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel-footer text-right">
                <a href="{{ route('admin.exenciones.index') }}" class="btn btn-default">
                    <i class="voyager-angle-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="voyager-check"></i> {{ ($exencion->exists ?? false) ? 'Actualizar' : 'Guardar' }}
                </button>
            </div>
        </div>
    </form>
</div>
@stop

@section('javascript')
<script>
    $(document).ready(function () {
        // Auto-dismiss alerts
        setTimeout(() => $('.auto-dismiss').fadeOut('slow', function(){ $(this).remove(); }), 5000);
        $('.auto-dismiss .close').click(function(e){
            e.preventDefault();
            $(this).closest('.alert').fadeOut('slow', function(){ $(this).remove(); });
        });

        // Trim al perder foco
        $('#nombre').on('blur', function () {
            $(this).val($(this).val().trim());
        });

        // Validación previa al enviar
        $('#exencion-form').on('submit', function () {
            const nombre = $('#nombre').val().trim();
            $('#nombre').val(nombre);

            if (!nombre) {
                alert('El nombre de la exención es obligatorio');
                $('#nombre').focus();
                return false;
            }
        });
    });
</script>
@stop
