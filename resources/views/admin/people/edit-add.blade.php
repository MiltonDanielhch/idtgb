@extends('voyager::master')

@section('page_title', ($person->exists ?? false) ? 'Editar Persona' : 'Agregar Persona')

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    <form action="{{ ($person->exists ?? false)
            ? route('admin.people.update', $person)
            : route('admin.people.store') }}"
          method="POST"
          enctype="multipart/form-data">
        @csrf
        @if($person->exists ?? false) @method('PUT') @endif

        <div class="panel panel-bordered panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="voyager-person"></i>
                    {{ ($person->exists ?? false) ? 'Editar' : 'Agregar' }} Persona
                </h3>
            </div>

            <div class="panel-body">
                <div class="row">
                    {{-- Tipo de persona --}}
                    <div class="col-md-4">
                        <label>Tipo de persona <span class="required">*</span></label>
                        <select name="person_type" id="person_type" class="form-control @error('person_type') is-invalid @enderror" required>
                            <option value="Natural" {{ old('person_type', optional($person)->person_type) == 'Natural' ? 'selected' : '' }}>Natural</option>
                            <option value="Jurídica" {{ old('person_type', optional($person)->person_type) == 'Jurídica' ? 'selected' : '' }}>Jurídica</option>
                        </select>
                        @error('person_type')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Tipo de documento --}}
                    <div class="col-md-4">
                        <label>Tipo de documento <span class="required">*</span></label>
                        <select name="tipo_doc" id="tipo_doc" class="form-control @error('tipo_doc') is-invalid @enderror" required>
                            <option value="CI" {{ old('tipo_doc', optional($person)->tipo_doc) == 'CI' ? 'selected' : '' }}>CI</option>
                            <option value="NIT" {{ old('tipo_doc', optional($person)->tipo_doc) == 'NIT' ? 'selected' : '' }}>NIT</option>
                        </select>
                        @error('tipo_doc')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- CI y Complemento (solo Natural) --}}
                    <div class="col-md-2 natural-field" style="{{ optional($person)->person_type == 'Jurídica' ? 'display:none' : '' }}">
                        <label>Documento</label>
                        <input type="text" name="ci" class="form-control @error('ci') is-invalid @enderror" value="{{ old('ci', optional($person)->ci) }}">
                        @error('ci')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-2 natural-field" style="{{ optional($person)->person_type == 'Jurídica' ? 'display:none' : '' }}">
                        <label>Comp.</label>
                        <input type="text" name="ci_complemento" class="form-control @error('ci_complemento') is-invalid @enderror" value="{{ old('ci_complemento', optional($person)->ci_complemento) }}">
                        @error('ci_complemento')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- NIT (solo Jurídica) --}}
                    <div class="col-md-4 juridica-field" style="{{ optional($person)->person_type != 'Jurídica' ? 'display:none' : '' }}">
                        <label>NIT <span class="required">*</span></label>
                        <input type="text" name="nit" class="form-control @error('nit') is-invalid @enderror" value="{{ old('nit', optional($person)->nit) }}">
                        @error('nit')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Razón social (solo Jurídica) --}}
                    <div class="col-md-8 juridica-field" style="{{ optional($person)->person_type != 'Jurídica' ? 'display:none' : '' }}">
                        <label>Razón social <span class="required">*</span></label>
                        <input type="text" name="legal_name" class="form-control @error('legal_name') is-invalid @enderror" value="{{ old('legal_name', optional($person)->getAttributes()['legal_name'] ?? '') }}" required>
                        @error('legal_name')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Nombre completo (solo Natural) --}}
                    <div class="col-md-6 natural-field" style="{{ optional($person)->person_type == 'Jurídica' ? 'display:none' : '' }}">
                        <label>Nombre completo <span class="required">*</span></label>
                        <input type="text" name="nombre_completo" class="form-control @error('nombre_completo') is-invalid @enderror" value="{{ old('nombre_completo', optional($person)->getAttributes()['nombre_completo'] ?? '') }}">
                        @error('nombre_completo')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>


                    {{-- Teléfono --}}
                    <div class="col-md-3">
                        <label>Teléfono / Celular</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', optional($person)->phone) }}">
                        @error('phone')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>



                </div>
            </div>

            <div class="panel-footer text-right">
                <a href="{{ route('admin.people.index') }}" class="btn btn-default">
                    <i class="voyager-angle-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="voyager-check"></i> {{ ($person->exists ?? false) ? 'Actualizar' : 'Guardar' }}
                </button>
            </div>
        </div>
    </form>
</div>
@stop

@push('javascript')
<script>
    function toggleFields() {
        const type = $('#person_type').val();
        if (type === 'Jurídica') {
            $('.juridica-field').show().find('input, select').prop('disabled', false);
            $('.natural-field').hide().find('input, select').prop('disabled', true);
            $('#tipo_doc').val('NIT');

            // Limpiar y remover clases de error de campos naturales
            $('.natural-field input, .natural-field select').val('').removeClass('is-invalid');
        } else {
            $('.juridica-field').hide().find('input, select').prop('disabled', true);
            $('.natural-field').show().find('input, select').prop('disabled', false);
            $('#tipo_doc').val('CI');

            // Limpiar y remover clases de error de campos jurídicos
            $('.juridica-field input, .juridica-field select').val('').removeClass('is-invalid');
        }
    }

    $(document).ready(function() {
        // Inicializar
        toggleFields();


        // Evento change
        $('#person_type').change(toggleFields);

        // Remover mensajes de error al cambiar tipo de persona
        $('#person_type').change(function() {
            // Ocultar mensajes de error que ya no aplican
            $('.text-danger').hide();
        });

        // Validación en tiempo real para campos requeridos
        $('form').on('submit', function(e) {
            let isValid = true;
            const type = $('#person_type').val();

            if (type === 'Jurídica') {
                // Validar campos jurídicos
                if (!$('input[name="nit"]').val()) {
                    $('input[name="nit"]').addClass('is-invalid');
                    isValid = false;
                }
                if (!$('input[name="legal_name"]').val()) {
                    $('input[name="legal_name"]').addClass('is-invalid');
                    isValid = false;
                }
            } else {
                // Validar campos naturales
                if (!$('input[name="nombre_completo"]').val()) {
                    $('input[name="nombre_completo"]').addClass('is-invalid');
                    isValid = false;
                }
            }

            if (!isValid) {
                e.preventDefault();
                // Scroll al primer error
                $('html, body').animate({
                    scrollTop: $('.is-invalid').first().offset().top - 100
                }, 500);
            }
        });

        // Remover clase de error al escribir
        $('input, select').on('input change', function() {
            $(this).removeClass('is-invalid');
        });
    });
</script>
@endpush
