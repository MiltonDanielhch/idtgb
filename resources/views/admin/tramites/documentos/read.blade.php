@extends('voyager::master')

@section('page_title', 'Detalle del Documento')

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    <div class="panel panel-bordered panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">
                <i class="voyager-eye"></i> Detalle del Documento
            </h3>
        </div>

        <div class="panel-body">
            <div class="row">
                {{-- Trámite --}}
                <div class="col-md-3">
                    <label>Trámite</label>
                    <p class="form-control-static">
                        <a href="{{ route('admin.tramites.show', $tramite) }}" class="btn btn-xs btn-warning">
                            <i class="voyager-eye"></i> {{ $tramite->nro_tramite }}
                        </a>
                    </p>
                </div>

                {{-- Tipo --}}
                <div class="col-md-2">
                    <label>Tipo</label>
                    <p class="form-control-static"><strong>{{ $item->tipo_doc }}</strong></p>
                </div>

                {{-- Versión --}}
                <div class="col-md-2">
                    <label>Versión</label>
                    <p class="form-control-static">
                        <span class="badge badge-info">v{{ $item->version }}</span>
                    </p>
                </div>

                {{-- Vigente --}}
                <div class="col-md-2">
                    <label>Vigente</label>
                    <p class="form-control-static">
                        @if($item->vigente)
                            <span class="label label-success">Vigente</span>
                        @else
                            <span class="label label-default">Obsoleto</span>
                        @endif
                    </p>
                </div>

                {{-- Persona --}}
                <div class="col-md-3">
                    <label>Persona</label>
                    <p class="form-control-static">{{ optional($item->persona)->fullName ?? '—' }}</p>
                </div>
            </div>

            <div class="row" style="margin-top: 15px;">
                {{-- Hash SHA-256 --}}
                <div class="col-md-12">
                    <label>Hash SHA-256</label>
                    <p class="form-control-static">
                        <small class="text-muted" style="word-break: break-all;">{{ $item->hash_sha256 ?? '—' }}</small>
                    </p>
                </div>
            </div>

            <div class="row" style="margin-top: 15px;">
                {{-- Archivo --}}
                <div class="col-md-12">
                    <label>Archivo</label>
                    <p class="form-control-static">
                        <a href="{{ \Storage::disk('public')->url($item->file_path) }}" target="_blank" class="btn btn-sm btn-primary">
                            <i class="voyager-download"></i> Descargar archivo
                        </a>
                        <small class="text-muted ml-2">{{ basename($item->file_path) }}</small>
                    </p>
                </div>
            </div>

            <div class="row" style="margin-top: 15px;">
                {{-- Registrado el --}}
                <div class="col-md-3">
                    <label>Registrado el</label>
                    <p class="form-control-static">{{ $item->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>

        <div class="panel-footer text-right">
            <a href="{{ route('admin.tramites.documentos.index', $tramite) }}" class="btn btn-default">
                <i class="voyager-angle-left"></i> Volver
            </a>
        </div>
    </div>
</div>
@stop
