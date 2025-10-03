@extends('voyager::master')

@section('page_title', 'Detalle del Disponente')

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    <div class="panel panel-bordered panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">
                <i class="voyager-eye"></i> Detalle del Disponente
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
                    <p class="form-control-static"><strong>{{ $item->persona->fullName }}</strong></p>
                </div>

                {{-- CI --}}
                <div class="col-md-2">
                    <label>CI</label>
                    <p class="form-control-static">{{ $item->persona->ci }}</p>
                </div>

                {{-- Tipo --}}
                <div class="col-md-2">
                    <label>Tipo</label>
                    <p class="form-control-static">
                        <span class="badge badge-info">{{ $item->tipo }}</span>
                    </p>
                </div>

                {{-- Discapacidad --}}
                <div class="col-md-2">
                    <label>Discapacidad</label>
                    <p class="form-control-static">
                        @if($item->es_discapacitado)
                            <span class="label label-warning">Sí</span>
                        @else
                            <span class="label label-default">No</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="row" style="margin-top: 15px;">
                {{-- Fecha fallecimiento --}}
                <div class="col-md-3">
                    <label>Fecha fallecimiento</label>
                    <p class="form-control-static">
                        @if($item->fecha_fallecimiento)
                            <span class="label label-default">{{ $item->fecha_fallecimiento->format('d/m/Y') }}</span>
                        @else
                            <span class="label label-success">Vivo</span>
                        @endif
                    </p>
                </div>

                {{-- Registrado el --}}
                <div class="col-md-3">
                    <label>Registrado el</label>
                    <p class="form-control-static">{{ $item->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>

        <div class="panel-footer text-right">
            <a href="{{ route('admin.tramites.disponentes.index', $tramite) }}" class="btn btn-default">
                <i class="voyager-angle-left"></i> Volver
            </a>
        </div>
    </div>
</div>
@stop
