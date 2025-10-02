@extends('voyager::master')

@section('page_title', ($person->exists ?? false) ? 'Editar Persona' : 'Agregar Persona')

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    <form action="{{ ($person->exists ?? false)
            ? route('admin.people.update',    $person)
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
                        <select name="person_type" id="person_type" class="form-control" required>
                            <option value="Natural" {{ old('person_type', optional($person)->person_type) == 'Natural' ? 'selected' : '' }}>Natural</option>
                            <option value="Jurídica" {{ old('person_type', optional($person)->person_type) == 'Jurídica' ? 'selected' : '' }}>Jurídica</option>
                        </select>
                    </div>

                    {{-- Tipo de documento --}}
                    <div class="col-md-4">
                        <label>Tipo de documento <span class="required">*</span></label>
                        <select name="tipo_doc" id="tipo_doc" class="form-control" required>
                            <option value="CI" {{ old('tipo_doc', optional($person)->tipo_doc) == 'CI' ? 'selected' : '' }}>CI</option>
                            <option value="NIT" {{ old('tipo_doc', optional($person)->tipo_doc) == 'NIT' ? 'selected' : '' }}>NIT</option>
                            <option value="PASS" {{ old('tipo_doc', optional($person)->tipo_doc) == 'PASS' ? 'selected' : '' }}>PASS</option>
                        </select>
                    </div>

                    {{-- CI y Complemento (solo Natural) --}}
                    <div class="col-md-2 natural-field" style="{{ optional($person)->person_type == 'Jurídica' ? 'display:none' : '' }}">
                        <label>Documento</label>
                        <input type="text" name="ci" class="form-control" value="{{ old('ci', optional($person)->ci) }}">
                    </div>
                    <div class="col-md-2 natural-field" style="{{ optional($person)->person_type == 'Jurídica' ? 'display:none' : '' }}">
                        <label>Comp.</label>
                        <input type="text" name="ci_complemento" class="form-control" value="{{ old('ci_complemento', optional($person)->ci_complemento) }}">
                    </div>

                    {{-- NIT (solo Jurídica) --}}
                    <div class="col-md-4 juridica-field" style="{{ optional($person)->person_type != 'Jurídica' ? 'display:none' : '' }}">
                        <label>NIT <span class="required">*</span></label>
                        <input type="text" name="nit" class="form-control" value="{{ old('nit', optional($person)->nit) }}">
                    </div>

                    {{-- Razón social (solo Jurídica) --}}
                    <div class="col-md-8 juridica-field" style="{{ optional($person)->person_type != 'Jurídica' ? 'display:none' : '' }}">
                        <label>Razón social</label>
                        <input type="text" name="legal_name" class="form-control" value="{{ old('legal_name', optional($person)->legal_name) }}" required>
                    </div>

                    {{-- Nombres y apellidos (solo Natural) --}}
                    <div class="col-md-3 natural-field" style="{{ optional($person)->person_type == 'Jurídica' ? 'display:none' : '' }}">
                        <label>Primer nombre <span class="required">*</span></label>
                        <input type="text" name="first_name" class="form-control" value="{{ old('first_name', optional($person)->first_name) }}">
                    </div>
                    <div class="col-md-3 natural-field" style="{{ optional($person)->person_type == 'Jurídica' ? 'display:none' : '' }}">
                        <label>Segundo nombre</label>
                        <input type="text" name="middle_name" class="form-control" value="{{ old('middle_name', optional($person)->middle_name) }}">
                    </div>
                    <div class="col-md-3 natural-field" style="{{ optional($person)->person_type == 'Jurídica' ? 'display:none' : '' }}">
                        <label>Apellido paterno <span class="required">*</span></label>
                        <input type="text" name="paternal_surname" class="form-control" value="{{ old('paternal_surname', optional($person)->paternal_surname) }}">
                    </div>
                    <div class="col-md-3 natural-field" style="{{ optional($person)->person_type == 'Jurídica' ? 'display:none' : '' }}">
                        <label>Apellido materno</label>
                        <input type="text" name="maternal_surname" class="form-control" value="{{ old('maternal_surname', optional($person)->maternal_surname) }}">
                    </div>

                    {{-- Fecha de nacimiento (solo Natural) --}}
                    <div class="col-md-3 natural-field" style="{{ optional($person)->person_type == 'Jurídica' ? 'display:none' : '' }}">
                        <label>Fecha de nacimiento</label>
                        <input type="date" name="birth_date" class="form-control" value="{{ old('birth_date', optional($person)->birth_date?->format('Y-m-d')) }}">
                    </div>

                    {{-- Género (solo Natural) --}}
                    <div class="col-md-3 natural-field" style="{{ optional($person)->person_type == 'Jurídica' ? 'display:none' : '' }}">
                        <label>Género</label>
                        <select name="gender" class="form-control">
                            <option value="">Seleccione...</option>
                            <option value="Masculino" {{ old('gender', optional($person)->gender) == 'Masculino' ? 'selected' : '' }}>Masculino</option>
                            <option value="Femenino" {{ old('gender', optional($person)->gender) == 'Femenino' ? 'selected' : '' }}>Femenino</option>
                        </select>
                    </div>

                    {{-- Email --}}
                    <div class="col-md-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', optional($person)->email) }}">
                    </div>

                    {{-- Teléfono --}}
                    <div class="col-md-3">
                        <label>Teléfono / Celular</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', optional($person)->phone) }}">
                    </div>

                    {{-- Dirección --}}
                    <div class="col-md-6">
                        <label>Dirección</label>
                        <textarea name="address" class="form-control" rows="2">{{ old('address', optional($person)->address) }}</textarea>
                    </div>

                    {{-- Fotografía --}}
                    <div class="col-md-6">
                        <label>Fotografía</label>
                        @if($person->image)
                            <div class="mb-2">
                                <img src="{{ asset('storage/'.$person->image) }}" alt="Foto actual" style="width: 120px; height: auto; border-radius: 4px;">
                            </div>
                        @endif
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>

                    {{-- Estado --}}
                    <div class="col-md-3">
                        <label>Estado</label>
                        <select name="status" class="form-control">
                            <option value="1" {{ old('status', optional($person)->status) == 1 ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ old('status', optional($person)->status) == 0 ? 'selected' : '' }}>Inactivo</option>
                            <option value="2" {{ old('status', optional($person)->status) == 2 ? 'selected' : '' }}>Pendiente</option>
                        </select>
                    </div>

                    {{-- Estado persona --}}
                    <div class="col-md-3">
                        <label>Estado persona</label>
                        <select name="estado_persona" class="form-control">
                            <option value="Activo" {{ old('estado_persona', optional($person)->estado_persona) == 'Activo' ? 'selected' : '' }}>Activo</option>
                            <option value="Inactivo" {{ old('estado_persona', optional($person)->estado_persona) == 'Inactivo' ? 'selected' : '' }}>Inactivo</option>
                            <option value="Fallecido" {{ old('estado_persona', optional($person)->estado_persona) == 'Fallecido' ? 'selected' : '' }}>Fallecido</option>
                        </select>
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
        } else {
            $('.juridica-field').hide().find('input, select').prop('disabled', true);
            $('.natural-field').show().find('input, select').prop('disabled', false);
            $('#tipo_doc option').show();
        }
    }
    $('#person_type').change(toggleFields);
    toggleFields(); // inicial
    // Limpiar valores de campos ocultos
    $('.juridica-field:hidden input').val('');
    $('.natural-field:hidden input').val('');
</script>
@endpush
