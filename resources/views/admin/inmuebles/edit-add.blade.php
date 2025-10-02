@extends('voyager::master')

@section('page_title', ($inmueble->exists ?? false) ? 'Editar Inmueble' : 'Agregar Inmueble')

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    <form action="{{ ($inmueble->exists ?? false)
            ? route('admin.inmuebles.update', $inmueble)
            : route('admin.inmuebles.store') }}"
          method="POST">
        @csrf
        @if($inmueble->exists ?? false) @method('PUT') @endif

        <div class="panel panel-bordered panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa-solid fa-building"></i>
                    {{ ($inmueble->exists ?? false) ? 'Editar' : 'Agregar' }} Inmueble
                </h3>
            </div>

            <div class="panel-body">
                <div class="row">
                    {{-- Complemento --}}
                    <div class="col-md-3">
                        <label>Complemento</label>
                        <input type="text" name="complemento" class="form-control"
                               value="{{ old('complemento', optional($inmueble)->complemento) }}"
                               maxlength="3" placeholder="Ej: A">
                    </div>

                    {{-- Catástro (único) --}}
                    <div class="col-md-3">
                        <label>Catástro <span class="required">*</span></label>
                        <input type="text" name="catastro" class="form-control"
                               value="{{ old('catastro', optional($inmueble)->catastro) }}"
                               required maxlength="15" placeholder="Ej: 123456789012345">
                    </div>

                    {{-- Tipo de inmueble --}}
                    <div class="col-md-3">
                        <label>Tipo Inmueble <span class="required">*</span></label>
                        <select name="tipo_inmueble_id" class="form-control select2" required>
                            <option value="">Elija...</option>
                            @foreach($tipos as $t)
                                <option value="{{ $t->id }}"
                                    {{ old('tipo_inmueble_id', optional($inmueble)->tipo_inmueble_id) == $t->id ? 'selected' : '' }}>
                                    {{ $t->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Municipio --}}
                    <div class="col-md-3">
                        <label>Municipio</label>
                        <select name="municipio_id" class="form-control select2">
                            <option value="">Ninguno</option>
                            @foreach($municipios as $m)
                                <option value="{{ $m->id }}"
                                    {{ old('municipio_id', optional($inmueble)->municipio_id) == $m->id ? 'selected' : '' }}>
                                    {{ $m->nombre }} ({{ $m->provincia->departamento->codigo }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row" style="margin-top: 15px;">
                    {{-- Barrio / Comunidad --}}
                    <div class="col-md-4">
                        <label>Barrio / Comunidad</label>
                        <input type="text" name="barrio_comunidad" class="form-control"
                               value="{{ old('barrio_comunidad', optional($inmueble)->barrio_comunidad) }}"
                               maxlength="100" placeholder="Ej: Barrio Los Álamos">
                    </div>

                    {{-- Dirección --}}
                    <div class="col-md-4">
                        <label>Dirección</label>
                        <input type="text" name="direccion" class="form-control"
                               value="{{ old('direccion', optional($inmueble)->direccion) }}"
                               maxlength="200" placeholder="Ej: Av. 6 de Agosto #123">
                    </div>

                    {{-- Matrícula RR --}}
                    <div class="col-md-4">
                        <label>Matrícula RR</label>
                        <input type="text" name="matricula_rr" class="form-control"
                               value="{{ old('matricula_rr', optional($inmueble)->matricula_rr) }}"
                               maxlength="20" placeholder="Ej: RR-123456">
                    </div>
                </div>

                <div class="row" style="margin-top: 15px;">
                    {{-- Superficie (m²) --}}
                    <div class="col-md-3">
                        <label>Superficie (m²)</label>
                        <input type="number" step="0.01" min="0" name="superficie_m2" class="form-control"
                               value="{{ old('superficie_m2', optional($inmueble)->superficie_m2) }}"
                               placeholder="Ej: 250.50">
                    </div>

                    {{-- Valor Catastral --}}
                    <div class="col-md-3">
                        <label>Valor Catastral <span class="required">*</span></label>
                        <input type="number" step="0.01" min="0" name="valor_catastral" class="form-control"
                               value="{{ old('valor_catastral', optional($inmueble)->valor_catastral) }}"
                               required placeholder="Ej: 500000.00">
                    </div>

                    {{-- Es vivienda única --}}
                    <div class="col-md-3">
                        <label>Es Vivienda Única</label>
                        <select name="es_vivienda_unica_familiar" class="form-control">
                            <option value="0" {{ !old('es_vivienda_unica_familiar', optional($inmueble)->es_vivienda_unica_familiar) ? 'selected' : '' }}>No</option>
                            <option value="1" {{ old('es_vivienda_unica_familiar', optional($inmueble)->es_vivienda_unica_familiar) ? 'selected' : '' }}>Sí</option>
                        </select>
                    </div>

                    {{-- Estado del inmueble --}}
                    <div class="col-md-3">
                        <label>Estado</label>
                        <select name="estado_inmueble" class="form-control">
                            @php
                                $estados = ['Activo', 'Transferido', 'Baja'];
                                $current = old('estado_inmueble', optional($inmueble)->estado_inmueble ?? 'Activo');
                            @endphp
                            @foreach($estados as $e)
                                <option value="{{ $e }}" {{ $current == $e ? 'selected' : '' }}>{{ $e }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="panel-footer text-right">
                <a href="{{ route('admin.inmuebles.index') }}" class="btn btn-default">
                    <i class="voyager-angle-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="voyager-check"></i> {{ ($inmueble->exists ?? false) ? 'Actualizar' : 'Guardar' }}
                </button>
            </div>
        </div>
    </form>
</div>
@stop
