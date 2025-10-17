@extends('voyager::master')

@section('page_title', ($item->exists ?? false) ? 'Editar Adquirente' : 'Agregar Adquirente')

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    <form action="{{ ($item->exists ?? false)
            ? route('admin.tramites.adquirentes.update', [$tramite, $item])
            : route('admin.tramites.adquirentes.store', $tramite) }}"
          method="POST"
          enctype="multipart/form-data">
        @csrf
        @if($item->exists ?? false) @method('PUT') @endif

        <div class="panel panel-bordered panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="voyager-people"></i>
                    {{ ($item->exists ?? false) ? 'Editar' : 'Agregar' }} Adquirente - Trámite {{ $tramite->nro_tramite }}
                </h3>
            </div>

            <div class="panel-body">
                <div class="row">
                    {{-- Persona --}}
                    <div class="col-md-5">
                        <label>Persona <span class="required">*</span></label>
                        <select name="person_id" class="form-control select2" required {{ ($item->exists ?? false) ? 'disabled' : '' }}>
                            <option value="">Elija...</option>
                           @foreach($personas as $p)
                                <option value="{{ $p->id }}">{{ $p->nombre_completo }} - {{ $p->tipo_doc }} {{ $p->ci }}</option>
                            @endforeach
                        </select>
                        @error('person_id') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    {{-- Parentesco --}}
                    <div class="col-md-3">
                        <label>Parentesco <span class="required">*</span></label>
                        <select name="parentesco_id" class="form-control select2" required>
                            <option value="">Elija...</option>
                            @foreach($parentescos as $p)
                                <option value="{{ $p->id }}"
                                    {{ old('parentesco_id', optional($item)->parentesco_id) == $p->id ? 'selected' : '' }}>
                                    {{ $p->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('parentesco_id') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    {{-- Porcentaje --}}
                    <div class="col-md-2">
                        <label>Porcentaje (%) <span class="required">*</span></label>
                        <input type="number" step="0.01" min="0.01" max="100" name="porcentaje" class="form-control"
                               value="{{ old('porcentaje', optional($item)->porcentaje) }}" required>
                        @error('porcentaje') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    {{-- Beneficiario exención --}}
                    <div class="col-md-2">
                        <label style="margin-top: 8px;">
                            <input type="checkbox" name="es_beneficiario_exencion" value="1"
                                {{ old('es_beneficiario_exencion', optional($item)->es_beneficiario_exencion) ? 'checked' : '' }}>
                            ¿Beneficiario exención?
                        </label>
                    </div>
                </div>

                <div class="row" style="margin-top: 15px;">
                    {{-- Documento de sustento --}}
                    <div class="col-md-6">
                        <label>Documento de sustento (PDF/JPG/PNG)</label>
                        <input type="file" name="documento_sustento_exencion" class="form-control"
                               accept=".pdf,.jpg,.jpeg,.png" {{ ($item->exists ?? false) ? '' : '' }}>
                        @if(($item->exists ?? false) && $item->documento_sustento_exencion)
                            <small>Archivo actual:
                                <a href="{{ \Storage::disk('public')->url($item->documento_sustento_exencion) }}" target="_blank">Ver</a>
                            </small>
                        @endif
                        @error('documento_sustento_exencion') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    {{-- Tasa sugerida (solo lectura) --}}
                    <div class="col-md-3">
                        <label>Tasa sugerida (%)</label>
                        <p class="form-control-static">
                            <span class="badge badge-info" id="tasa-sugerida">--</span>
                        </p>
                    </div>

                    {{-- IDTGB proporcional (solo lectura) --}}
                    <div class="col-md-3">
                        <label>IDTGB proporcional</label>
                        <p class="form-control-static">
                            <span class="badge badge-success">Bs. {{ number_format(optional($item)->idtgb_proporcional ?? 0, 2) }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="panel-footer text-right">
                <a href="{{ route('admin.tramites.adquirentes.index', $tramite) }}" class="btn btn-default">
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

        // Si en el futuro agregas edit, aquí podrías recalcular la tasa vía AJAX
        // Por ahora se deja placeholder para mantener el patrón
    });
</script>
@stop
