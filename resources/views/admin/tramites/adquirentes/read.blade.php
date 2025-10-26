@extends('voyager::master')

@section('page_title', 'Detalle del Adquirente')

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    <div class="panel panel-bordered panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">
                <i class="voyager-eye"></i> Detalle del Adquirente
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

                {{-- Persona --}}
                <div class="col-md-3">
                    <label>Persona</label>
                    <p class="form-control-static"><strong>{{ optional($item->person)->display_name ?? optional($item->person)->full_name ?? 'Nombre no definido' }}</strong></p>
                </div>

                {{-- CI --}}
                <div class="col-md-2">
                    <label>CI</label>
                    <p class="form-control-static">{{ $item->person->ci }}</p>
                </div>

                {{-- Parentesco --}}
                <div class="col-md-2">
                    <label>Parentesco</label>
                    <p class="form-control-static">{{ $item->parentesco->nombre }}</p>
                </div>

                {{-- Porcentaje --}}
                <div class="col-md-2">
                    <label>Porcentaje</label>
                    <p class="form-control-static">
                        <span class="badge badge-primary">{{ $item->porcentaje }} %</span>
                    </p>
                </div>
            </div>

            <div class="row" style="margin-top: 15px;">
                {{-- Tasa aplicada --}}
                <div class="col-md-3">
                    <label>Tasa Aplicada</label>
                    <p class="form-control-static">
                        <span class="badge badge-info">{{ $item->tasa_aplicada }} %</span>
                    </p>
                </div>

                {{-- IDTGB proporcional --}}
                <div class="col-md-3">
                    <label>IDTGB Proporcional</label>
                    <p class="form-control-static">
                        <span class="badge badge-success">Bs. {{ number_format($item->idtgb_proporcional, 2) }}</span>
                    </p>
                </div>

                {{-- Beneficiario exención --}}
                <div class="col-md-3">
                    <label>Beneficiario Exención</label>
                    <p class="form-control-static">
                        @if($item->es_beneficiario_exencion)
                            <span class="label label-success">Sí</span>
                        @else
                            <span class="label label-default">No</span>
                        @endif
                    </p>
                </div>

                {{-- Documento de sustento --}}
                <div class="col-md-3">
                    <label>Documento Sustento</label>
                    <p class="form-control-static">
                        @if($item->documento_sustento_exencion)
                            <a href="{{ \Storage::disk('public')->url($item->documento_sustento_exencion) }}" target="_blank" class="btn btn-xs btn-primary">
                                <i class="voyager-eye"></i> Ver archivo
                            </a>
                        @else
                            <span class="text-muted">No subido</span>
                        @endif
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
            <a href="{{ route('admin.tramites.adquirentes.index', $tramite) }}" class="btn btn-default">
                <i class="voyager-angle-left"></i> Volver
            </a>
        </div>
    </div>
</div>
@stop
