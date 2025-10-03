@extends('voyager::master')

@section('page_title', 'Detalle del Pago')

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    <div class="panel panel-bordered panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">
                <i class="voyager-dollar"></i> Detalle del Pago
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

                {{-- Fecha de pago --}}
                <div class="col-md-3">
                    <label>Fecha de pago</label>
                    <p class="form-control-static">{{ $pago->fecha_pago->format('d/m/Y H:i') }}</p>
                </div>

                {{-- Monto --}}
                <div class="col-md-2">
                    <label>Monto</label>
                    <p class="form-control-static">
                        <strong>Bs. {{ number_format($pago->monto, 2) }}</strong>
                    </p>
                </div>

                {{-- Estado --}}
                <div class="col-md-2">
                    <label>Estado</label>
                    <p class="form-control-static">
                        @php
                            $badge = match($pago->estado) {
                                'Aplicado'   => 'success',
                                'Reversado'  => 'danger',
                                default      => 'warning'
                            };
                        @endphp
                        <span class="label label-{{ $badge }}">{{ $pago->estado }}</span>
                    </p>
                </div>

                {{-- Banco --}}
                <div class="col-md-2">
                    <label>Banco</label>
                    <p class="form-control-static">{{ $pago->banco ?? '—' }}</p>
                </div>
            </div>

            <div class="row" style="margin-top: 15px;">
                {{-- Nro operación --}}
                <div class="col-md-3">
                    <label>Nro operación</label>
                    <p class="form-control-static"><code>{{ $pago->nro_operacion ?? '—' }}</code></p>
                </div>

                {{-- Código de barras --}}
                <div class="col-md-3">
                    <label>Código de barras</label>
                    <p class="form-control-static">
                        <small class="text-muted">{{ $pago->codigo_barras }}</small>
                    </p>
                </div>

                {{-- Conciliado el --}}
                <div class="col-md-3">
                    <label>Conciliado el</label>
                    <p class="form-control-static">
                        @if($pago->conciliado_el)
                            {{ $pago->conciliado_el->format('d/m/Y H:i') }}
                        @else
                            <span class="label label-default">Pendiente</span>
                        @endif
                    </p>
                </div>

                {{-- Registrado por --}}
                <div class="col-md-3">
                    <label>Registrado por</label>
                    <p class="form-control-static">{{ optional($pago->user)->name ?? '—' }}</p>
                </div>
            </div>

            <div class="row" style="margin-top: 15px;">
                {{-- Comprobante PDF --}}
                <div class="col-md-12">
                    <label>Comprobante PDF</label>
                    <p class="form-control-static">
                        <a href="{{ route('admin.pago.comprobante', $pago) }}" target="_blank" class="btn btn-sm btn-primary">
                            <i class="voyager-documentation"></i> Descargar comprobante
                        </a>
                    </p>
                </div>
            </div>
        </div>

        <div class="panel-footer text-right">
            <a href="{{ route('admin.tramites.pagos.index', $tramite) }}" class="btn btn-default">
                <i class="voyager-angle-left"></i> Volver
            </a>
        </div>
    </div>
</div>
@stop
