@extends('voyager::master')

@section('page_title', ($item->exists ?? false) ? 'Editar Disponente' : 'Agregar Disponente')

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    <form action="{{ ($item->exists ?? false)
            ? route('admin.tramites.disponentes.update', [$tramite, $item])
            : route('admin.tramites.disponentes.store', $tramite) }}"
          method="POST"
          enctype="multipart/form-data">
        @csrf
        @if($item->exists ?? false) @method('PUT') @endif

        <div class="panel panel-bordered panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="voyager-person"></i>
                    {{ ($item->exists ?? false) ? 'Editar' : 'Agregar' }} Disponente - Trámite {{ $tramite->nro_tramite }}
                </h3>
            </div>

            <div class="panel-body">
                <div class="row">
                    {{-- Persona --}}
                    <div class="col-md-4">
                        <label>Persona <span class="required">*</span></label>
                        <select name="persona_id" class="form-control select2" required {{ ($item->exists ?? false) ? 'disabled' : '' }}>
                            <option value="">Elija...</option>
                            @foreach($personas as $p)
                                <option value="{{ $p->id }}"
                                    {{ old('persona_id', optional($item)->persona_id) == $p->id ? 'selected' : '' }}>
                                    {{ $p->fullName }} - {{ $p->tipo_doc }} {{ $p->ci }}
                                </option>
                            @endforeach
                        </select>
                        @error('persona_id') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    {{-- Tipo --}}
                    <div class="col-md-3">
                        <label>Tipo <span class="required">*</span></label>
                        <select name="tipo" class="form-control" required>
                            <option value="">Elija...</option>
                            @foreach($tipos as $t)
                                <option value="{{ $t }}"
                                    {{ old('tipo', optional($item)->tipo) == $t ? 'selected' : '' }}>
                                    {{ $t }}
                                </option>
                            @endforeach
                        </select>
                        @error('tipo') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    {{-- Fecha fallecimiento --}}
                    <div class="col-md-3">
                        <label>Fecha fallecimiento</label>
                        <input type="date" name="fecha_fallecimiento" class="form-control"
                               value="{{ old('fecha_fallecimiento', optional($item)->fecha_fallecimiento?->format('Y-m-d')) }}"
                               max="{{ today()->format('Y-m-d') }}">
                        @error('fecha_fallecimiento') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    {{-- Discapacidad --}}
                    <div class="col-md-2">
                        <label style="margin-top: 8px;">
                            <input type="checkbox" name="es_discapacitado" value="1"
                                {{ old('es_discapacitado', optional($item)->es_discapacitado) ? 'checked' : '' }}>
                            ¿Discapacitado?
                        </label>
                    </div>
                </div>
            </div>

            <div class="panel-footer text-right">
                <a href="{{ route('admin.tramites.disponentes.index', $tramite) }}" class="btn btn-default">
                    <i class="voyager-angle-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="voyager-check"></i> {{ ($item->exists ?? false) ? 'Actualizar' : 'Guardar' }}
                </button>
            </div>
        </div>
    </form>
</div>
@stop

@section('javascript')
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: 'Elija...',
            allowClear: true,
            width: '100%'
        });
    });
</script>
@stop
