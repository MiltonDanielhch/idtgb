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
                            <option value="PASS" {{ old('tipo_doc', optional($person)->tipo_doc) == 'PASS' ? 'selected' : '' }}>PASS</option>
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
                        <input type="text" name="legal_name" class="form-control @error('legal_name') is-invalid @enderror" value="{{ old('legal_name', optional($person)->legal_name) }}" required>
                        @error('legal_name')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Nombres y apellidos (solo Natural) --}}
                    <div class="col-md-3 natural-field" style="{{ optional($person)->person_type == 'Jurídica' ? 'display:none' : '' }}">
                        <label>Primer nombre <span class="required">*</span></label>
                        <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', optional($person)->first_name) }}">
                        @error('first_name')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 natural-field" style="{{ optional($person)->person_type == 'Jurídica' ? 'display:none' : '' }}">
                        <label>Segundo nombre</label>
                        <input type="text" name="middle_name" class="form-control @error('middle_name') is-invalid @enderror" value="{{ old('middle_name', optional($person)->middle_name) }}">
                        @error('middle_name')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 natural-field" style="{{ optional($person)->person_type == 'Jurídica' ? 'display:none' : '' }}">
                        <label>Apellido paterno <span class="required">*</span></label>
                        <input type="text" name="paternal_surname" class="form-control @error('paternal_surname') is-invalid @enderror" value="{{ old('paternal_surname', optional($person)->paternal_surname) }}">
                        @error('paternal_surname')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 natural-field" style="{{ optional($person)->person_type == 'Jurídica' ? 'display:none' : '' }}">
                        <label>Apellido materno</label>
                        <input type="text" name="maternal_surname" class="form-control @error('maternal_surname') is-invalid @enderror" value="{{ old('maternal_surname', optional($person)->maternal_surname) }}">
                        @error('maternal_surname')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Fecha de nacimiento (solo Natural) --}}
                    <div class="col-md-3 natural-field" style="{{ optional($person)->person_type == 'Jurídica' ? 'display:none' : '' }}">
                        <label>Fecha de nacimiento</label>
                        <input type="date" name="birth_date" class="form-control @error('birth_date') is-invalid @enderror" value="{{ old('birth_date', optional($person)->birth_date?->format('Y-m-d')) }}">
                        @error('birth_date')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Género (solo Natural) --}}
                    <div class="col-md-3 natural-field" style="{{ optional($person)->person_type == 'Jurídica' ? 'display:none' : '' }}">
                        <label>Género</label>
                        <select name="gender" class="form-control @error('gender') is-invalid @enderror">
                            <option value="">Seleccione...</option>
                            <option value="Masculino" {{ old('gender', optional($person)->gender) == 'Masculino' ? 'selected' : '' }}>Masculino</option>
                            <option value="Femenino" {{ old('gender', optional($person)->gender) == 'Femenino' ? 'selected' : '' }}>Femenino</option>
                        </select>
                        @error('gender')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div class="col-md-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', optional($person)->email) }}">
                        @error('email')
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

                    {{-- Dirección --}}
                    <div class="col-md-6">
                        <label>Dirección</label>
                        <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', optional($person)->address) }}</textarea>
                        @error('address')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Fotografía --}}
                    <div class="col-md-6">
                        <label>Fotografía</label>
                        @if(($person->exists ?? false) && $person->image)
                            <div class="mb-2">
                                <img src="{{ asset('storage/'.$person->image) }}" alt="Foto actual" style="width: 120px; height: auto; border-radius: 4px;">
                                <div class="mt-1">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="remove_image" value="1"> Eliminar imagen actual
                                    </label>
                                </div>
                            </div>
                        @endif
                        <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept="image/*">
                        @error('image')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Estado --}}
                    <div class="col-md-3">
                        <label>Estado</label>
                        <select name="status" class="form-control @error('status') is-invalid @enderror">
                            <option value="1" {{ old('status', optional($person)->status ?? 1) == 1 ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ old('status', optional($person)->status ?? 1) == 0 ? 'selected' : '' }}>Inactivo</option>
                            <option value="2" {{ old('status', optional($person)->status ?? 1) == 2 ? 'selected' : '' }}>Pendiente</option>
                        </select>
                        @error('status')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Estado persona --}}
                    <div class="col-md-3">
                        <label>Estado persona</label>
                        <select name="estado_persona" class="form-control @error('estado_persona') is-invalid @enderror">
                            <option value="Activo" {{ old('estado_persona', optional($person)->estado_persona ?? 'Activo') == 'Activo' ? 'selected' : '' }}>Activo</option>
                            <option value="Inactivo" {{ old('estado_persona', optional($person)->estado_persona ?? 'Activo') == 'Inactivo' ? 'selected' : '' }}>Inactivo</option>
                            <option value="Fallecido" {{ old('estado_persona', optional($person)->estado_persona ?? 'Activo') == 'Fallecido' ? 'selected' : '' }}>Fallecido</option>
                        </select>
                        @error('estado_persona')
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
            $('#tipo_doc option').hide();
            $('#tipo_doc option[value="NIT"]').show();

            // Limpiar y remover clases de error de campos naturales
            $('.natural-field input, .natural-field select').val('').removeClass('is-invalid');
        } else {
            $('.juridica-field').hide().find('input, select').prop('disabled', true);
            $('.natural-field').show().find('input, select').prop('disabled', false);
            $('#tipo_doc option').show();

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
                if (!$('input[name="first_name"]').val()) {
                    $('input[name="first_name"]').addClass('is-invalid');
                    isValid = false;
                }
                if (!$('input[name="paternal_surname"]').val()) {
                    $('input[name="paternal_surname"]').addClass('is-invalid');
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
