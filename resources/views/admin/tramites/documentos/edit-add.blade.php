@extends('voyager::master')

@section('page_title', ($item->exists ?? false) ? 'Editar Documento' : 'Agregar Documento')

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    <form action="{{ ($item->exists ?? false)
            ? route('admin.tramites.documentos.update', [$tramite, $item])
            : route('admin.tramites.documentos.store', $tramite) }}"
          method="POST"
          enctype="multipart/form-data">
        @csrf
        @if($item->exists ?? false) @method('PUT') @endif

        <div class="panel panel-bordered panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="voyager-folder"></i>
                    {{ ($item->exists ?? false) ? 'Editar' : 'Agregar' }} Documento - Trámite {{ $tramite->nro_tramite }}
                </h3>
            </div>

            <div class="panel-body">
                <div class="row">
                    {{-- Tipo de documento --}}
                    <div class="col-md-3">
                        <label>Tipo <span class="required">*</span></label>
                        <select name="tipo_doc" class="form-control" required {{ ($item->exists ?? false) ? 'disabled' : '' }}>
                            <option value="">Elija...</option>
                            @foreach($tipos as $t)
                                <option value="{{ $t }}"
                                    {{ old('tipo_doc', optional($item)->tipo_doc) == $t ? 'selected' : '' }}>
                                    {{ $t }}
                                </option>
                            @endforeach
                        </select>
                        @error('tipo_doc') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    {{-- Archivo --}}
                    <div class="col-md-4">
                        <label>Archivo (PDF/JPG/PNG) <span class="required">*</span></label>
                        <input type="file" name="archivo" class="form-control"
                               accept=".pdf,.jpg,.jpeg,.png" {{ ($item->exists ?? false) ? 'disabled' : '' }}>
                        @if(($item->exists ?? false) && $item->file_path)
                            <small>Archivo actual:
                                <a href="{{ \Storage::disk('public')->url($item->file_path) }}" target="_blank">Ver</a>
                            </small>
                        @endif
                        @error('archivo') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    {{-- Persona (opcional) --}}
                    <div class="col-md-5">
                        <label>Persona (opcional)</label>
                        <select name="persona_id" class="form-control select2">
                            <option value="">-- Ninguna --</option>
                            @foreach($personas as $p)
                                <option value="{{ $p->id }}"
                                    {{ old('persona_id', optional($item)->persona_id) == $p->id ? 'selected' : '' }}>
                                    {{ $p->fullName }} - {{ $p->tipo_doc }} {{ $p->ci }}
                                </option>
                            @endforeach
                        </select>
                        @error('persona_id') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="row" style="margin-top: 15px;">
                    {{-- Versión actual (solo lectura) --}}
                    <div class="col-md-3">
                        <label>Versión actual</label>
                        <p class="form-control-static">
                            <span class="badge badge-info">v{{ optional($item)->version ?? 'nueva' }}</span>
                        </p>
                    </div>

                    {{-- Hash SHA-256 (solo lectura) --}}
                    <div class="col-md-9">
                        <label>Hash SHA-256</label>
                        <p class="form-control-static">
                            <small class="text-muted">{{ optional($item)->hash_sha256 ? Str::limit($item->hash_sha256, 64, '') : '—' }}</small>
                        </p>
                    </div>
                </div>
            </div>

            <div class="panel-footer text-right">
                <a href="{{ route('admin.tramites.documentos.index', $tramite) }}" class="btn btn-default">
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
