@extends('voyager::master')

@section('page_title', 'Detalle de Exención aplicada')

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    <div class="panel panel-bordered panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">
                <i class="voyager-eye"></i> Detalle de Exención aplicada
            </h3>
        </div>

        <div class="panel-body">
            <div class="row">
                {{-- Trámite --}}
                <div class="col-md-4">
                    <label>Trámite</label>
                    <p class="form-control-static">
                        <a href="{{ route('admin.tramites.show', $tramite) }}" class="btn btn-xs btn-warning">
                            <i class="voyager-eye"></i> {{ $tramite->nro_tramite }}
                        </a>
                    </p>
                </div>

                {{-- Exención --}}
                <div class="col-md-4">
                    <label>Exención</label>
                    <p class="form-control-static"><strong>{{ $item->exencion->nombre }}</strong></p>
                </div>

                {{-- Tipo y valor --}}
                <div class="col-md-4">
                    <label>Tipo / Valor</label>
                    <p class="form-control-static">
                        <span class="badge badge-info">
                            {{ $item->exencion->tipo === 'porcentaje' ? $item->exencion->valor.' %' : 'Bs. '.number_format($item->exencion->valor, 2) }}
                        </span>
                    </p>
                </div>
            </div>

            <div class="row" style="margin-top: 15px;">
                {{-- Monto aplicado --}}
                <div class="col-md-4">
                    <label>Monto Aplicado</label>
                    <p class="form-control-static">
                        <span class="badge badge-success">Bs. {{ number_format($item->monto_aplicado, 2) }}</span>
                    </p>
                </div>

                {{-- Vigencia --}}
                <div class="col-md-4">
                    <label>Vigencia de la exención</label>
                    <p class="form-control-static">
                        {{ $item->exencion->vigente_desde->format('d/m/Y') }}
                        -
                        {{ $item->exencion->vigente_hasta?->format('d/m/Y') ?? '∞' }}
                    </p>
                </div>

                {{-- Fecha de aplicación --}}
                <div class="col-md-4">
                    <label>Aplicada el</label>
                    <p class="form-control-static">{{ $item->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>

        <div class="panel-footer text-right">
            <a href="{{ route('admin.tramites.exenciones.index', $tramite) }}" class="btn btn-default">
                <i class="voyager-angle-left"></i> Volver
            </a>
        </div>
    </div>
</div>
@stop
