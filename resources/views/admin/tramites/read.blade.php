@extends('voyager::master')

@section('page_title', 'Ficha del Trámite '.$tramite->nro_tramite)

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    {{-- DATOS GENERALES --}}
    <div class="panel panel-bordered panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="fa-solid fa-file-lines"></i> Datos generales del trámite</h3>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-3"><label>Nro Trámite</label><p class="form-control-static"><strong>{{ $tramite->nro_tramite }}</strong></p></div>
                <div class="col-md-3"><label>Fecha Presentación</label><p class="form-control-static">{{ $tramite->fecha_presentacion->format('d/m/Y') }}</p></div>
                <div class="col-md-3"><label>Tipo Transmisión</label><p class="form-control-static">{{ $tramite->tipoTransmision->nombre }}</p></div>
                <div class="col-md-3"><label>Estado</label>
                    @php $badge = match($tramite->estado) { 'Pagado' => 'success', 'Borrador' => 'default', 'Observado' => 'warning', 'Anulado' => 'danger', 'Finalizado' => 'info', default => 'secondary' }; @endphp
                    <p><span class="badge badge-{{ $badge }}">{{ $tramite->estado }}</span></p>
                </div>
            </div>
            <div class="row" style="margin-top: 15px;">
                <div class="col-md-3"><label>Valor Declarado</label><p class="badge badge-secondary">Bs. {{ number_format($tramite->valor_declarado, 2) }}</p></div>
                <div class="col-md-3"><label>Base Imponible</label><p class="badge badge-primary">Bs. {{ number_format($tramite->base_imponible, 2) }}</p></div>
                <div class="col-md-3"><label>Total IDTGB</label><p class="badge badge-success">Bs. {{ number_format($tramite->total_idtgb, 2) }}</p></div>
                <div class="col-md-3"><label>Vencimiento</label><p class="text-{{ now()->gt($tramite->fecha_vencimiento) ? 'danger' : 'muted' }}">{{ $tramite->fecha_vencimiento->format('d/m/Y') }}</p></div>
            </div>
            <div class="row" style="margin-top: 15px;">
                <div class="col-md-12"><label>Observaciones</label><p class="form-control-static">{{ $tramite->observaciones ?? 'Sin observaciones' }}</p></div>
            </div>
        </div>
    </div>

    {{-- ACCESOS RÁPIDOS --}}
    <div class="panel panel-bordered panel-info">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="voyager-wrench"></i> Accesos rápidos</h3>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-3"><a href="{{ route('admin.tramites.inmuebles.index', $tramite) }}" class="btn btn-block btn-default"><i class="voyager-home"></i> Inmuebles</a></div>
                <div class="col-md-3"><a href="{{ route('admin.tramites.adquirentes.index', $tramite) }}" class="btn btn-block btn-default"><i class="voyager-people"></i> Adquirentes</a></div>
                <div class="col-md-3"><a href="{{ route('admin.tramites.disponentes.index', $tramite) }}" class="btn btn-block btn-default"><i class="voyager-person"></i> Disponentes</a></div>
                <div class="col-md-3"><a href="{{ route('admin.tramites.exenciones.index', $tramite) }}" class="btn btn-block btn-default"><i class="voyager-gift"></i> Exenciones</a></div>
            </div>
            <div class="row" style="margin-top: 10px;">
                <div class="col-md-3"><a href="{{ route('admin.tramites.documentos.index', $tramite) }}" class="btn btn-block btn-default"><i class="voyager-folder"></i> Documentos</a></div>
                <div class="col-md-3">
                    @if($tramite->estado !== 'Pagado')
                        <a href="{{ route('admin.tramites.pagos.create', $tramite) }}" class="btn btn-block btn-success"><i class="voyager-dollar"></i> Registrar pago</a>
                    @else
                        <a href="{{ route('admin.pago.comprobante', $tramite->pago) }}" target="_blank" class="btn btn-block btn-primary"><i class="voyager-check"></i> Comprobante</a>
                    @endif
                </div>
                <div class="col-md-3"><a href="{{ route('admin.tramites.a01', $tramite) }}" target="_blank" class="btn btn-block btn-warning"><i class="voyager-documentation"></i> Form. A-01 PDF</a></div>
                <div class="col-md-3">
                    @if($tramite->estado === 'Borrador')
                        <form action="{{ route('admin.tramites.destroy', $tramite) }}" method="POST" onsubmit="return confirm('¿Anular trámite?')" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-block btn-danger"><i class="voyager-trash"></i> Anular</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- RESUMEN DE ELEMENTOS --}}
    <div class="panel panel-bordered panel-success">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="voyager-info-circled"></i> Resumen del trámite</h3>
        </div>
        <div class="panel-body">
            <div class="row text-center">
                <div class="col-md-3">
                    <h4>{{ $tramite->inmueble ? 1 : 0 }}</h4>
                    <small>Inmuebles</small>
                </div>
                <div class="col-md-3">
                    <h4>{{ $tramite->adquirentes?->count() ?? 0 }}</h4>
                    <small>Adquirentes</small>
                </div>
                <div class="col-md-3">
                    <h4>{{ $tramite->disponentes?->count() ?? 0 }}</h4>
                    <small>Disponentes</small>
                </div>
                <div class="col-md-3">
                    <h4>{{ $tramite->exenciones?->count() ?? 0 }}</h4>
                    <small>Exenciones</small>
                </div>
            </div>
        </div>
    </div>

    {{-- AUDITORÍA --}}
    <div class="panel panel-bordered panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="voyager-clock"></i> Auditoría</h3>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6"><label>Presentado por</label><p class="form-control-static">{{ $tramite->user->name }}</p></div>
                <div class="col-md-3"><label>Creado</label><p class="form-control-static">{{ $tramite->created_at->format('d/m/Y H:i') }}</p></div>
                <div class="col-md-3"><label>Última modificación</label><p class="form-control-static">{{ $tramite->updated_at->format('d/m/Y H:i') }}</p></div>
            </div>
        </div>
    </div>
</div>
@stop
