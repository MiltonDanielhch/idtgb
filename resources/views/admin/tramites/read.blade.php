@extends('voyager::master')

@section('page_title', 'Ficha del Trámite '.$tramite->nro_tramite)

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    {{-- DOCUMENTACIÓN Y DESCARGA (Formulario PDF Rediseñado) --}}
    <div class="panel panel-bordered panel-warning">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="voyager-documentation"></i> Documentación del Trámite</h3>
        </div>
        <div class="panel-body" style="background: #fdfaf2;">
            <div class="row flex-align-center" style="display: flex; align-items: center; flex-wrap: wrap;">
                <div class="col-md-8">
                    <h4 style="margin-top: 0; color: #8a6d3b; font-weight: bold;">Formulario Oficial A-01 (IDTGB)</h4>
                    <p class="text-muted">Descargue el expediente consolidado en formato PDF que contiene la declaración jurada, liquidación de la base imponible y el cálculo del impuesto para las firmas correspondientes.</p>
                </div>
                <div class="col-md-4 text-right">
                    <a href="{{ route('admin.tramites.a01', $tramite) }}" target="_blank" class="btn btn-lg btn-warning btn-block" style="font-weight: bold; font-size: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <i class="voyager-download"></i> Descargar Form. A-01 PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

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
                <div class="col-md-3"><label>Categoría Tasa</label><p class="badge badge-info">{{ $tramite->categoria_tasa == 1 ? 'Línea Directa (1%)' : ($tramite->categoria_tasa == 10 ? 'Colateral (10%)' : 'Otros (20%)') }}</p></div>
                <div class="col-md-3"><label>Vencimiento</label><p class="text-{{ now()->gt($tramite->fecha_vencimiento) ? 'danger' : 'muted' }}">{{ $tramite->fecha_vencimiento->format('d/m/Y') }}</p></div>
            </div>
            <div class="row" style="margin-top: 15px;">
                <div class="col-md-12"><label>Observaciones</label><p class="form-control-static">{{ $tramite->observaciones ?? 'Sin observaciones' }}</p></div>
            </div>
        </div>
    </div>

    {{-- ADQUIRENTE PRINCIPAL --}}
    @if($tramite->adquirentes->first())
    <div class="panel panel-bordered panel-info">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="voyager-people"></i> Adquirente Principal</h3>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6"><label>Nombre Completo</label><p class="form-control-static"><strong>{{ $tramite->adquirentes->first()->person->fullName ?? 'N/A' }}</strong></p></div>
                <div class="col-md-3"><label>Carnet Identidad</label><p class="form-control-static">{{ $tramite->adquirentes->first()->person->ci ?? 'N/A' }}</p></div>
                <div class="col-md-3"><label>Tipo Persona</label><p class="form-control-static">{{ $tramite->adquirentes->first()->person->person_type ?? 'N/A' }}</p></div>
            </div>
        </div>
    </div>
    @endif

    {{-- LIQUIDACIÓN (LEY 812) --}}
    <div class="panel panel-bordered panel-success">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="voyager-calculator"></i> Liquidación (Ley 812)</h3>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-3">
                    <label>Tributo Omitido (Base)</label>
                    <p class="form-control-static">Bs. {{ number_format($tramite->total_idtgb, 2) }}</p>
                </div>
                <div class="col-md-3">
                    <label>Tributo Actualizado (UFV)</label>
                    <p class="form-control-static text-primary"><strong>Bs. {{ number_format($tramite->tributo_actualizado ?? $tramite->total_idtgb, 2) }}</strong></p>
                </div>
                <div class="col-md-3">
                    <label>Intereses (Mora)</label>
                    <p class="form-control-static text-warning">Bs. {{ number_format($tramite->recargo_mora, 2) }}</p>
                </div>
                <div class="col-md-3">
                    <label>Multa IDF</label>
                    <p class="form-control-static text-danger">Bs. {{ number_format($tramite->multa_idf ?? 0, 2) }}</p>
                </div>
            </div>
            <div class="row" style="margin-top: 15px;">
                <div class="col-md-3">
                    <label>UFV Vencimiento</label>
                    <p class="form-control-static">{{ number_format($tramite->ufv_vencimiento ?? 0, 5) }}</p>
                </div>
                <div class="col-md-3">
                    <label>UFV Pago</label>
                    <p class="form-control-static">{{ number_format($tramite->ufv_aplicada, 5) }}</p>
                </div>
                <div class="col-md-3">
                    <label>Días Mora</label>
                    <p class="form-control-static">{{ $tramite->dias_mora ?? 0 }}</p>
                </div>
                <div class="col-md-3">
                    <label>Monto Final</label>
                    <p class="form-control-static text-success" style="font-size: 1.2em; font-weight: bold;">Bs. {{ number_format(round($tramite->monto_final * 2) / 2, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ACCIONES DE CONTROL DE FLUJO --}}
    <!-- <div class="panel panel-bordered panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="voyager-paper-plane"></i> Operaciones de Gestión</h3>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-4">
                    @if($tramite->estado !== 'Pagado')
                        <a href="{{ route('admin.tramites.pagos.create', $tramite) }}" class="btn btn-block btn-success btn-lg">
                            <i class="voyager-dollar"></i> Registrar Caja / Recaudación
                        </a>
                    @else
                        @php $pago = $tramite->pagos->where('estado', 'Aplicado')->first(); @endphp
                        @if($pago)
                            <a href="{{ route('admin.tramites.pagos.show', ['tramite' => $tramite, 'pago' => $pago]) }}" target="_blank" class="btn btn-block btn-primary btn-lg">
                                <i class="voyager-check"></i> Ver Comprobante de Pago Oficial
                            </a>
                        @endif
                    @endif
                </div>
                <div class="col-md-4">
                    @if(!in_array($tramite->estado, ['Finalizado', 'Anulado', 'Pagado']))
                        <a href="{{ route('admin.tramites.simple.create') }}" class="btn btn-block btn-warning btn-lg">
                            <i class="voyager-plus"></i> Iniciar Flujo Simplificado
                        </a>
                    @endif
                </div>
                <div class="col-md-4">
                    @if($tramite->estado === 'Borrador')
                        <form action="{{ route('admin.tramites.destroy', $tramite) }}" method="POST" onsubmit="return confirm('¿Está completamente seguro de anular definitivamente este trámite?')" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-block btn-danger btn-lg"><i class="voyager-trash"></i> Anular Trámite</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div> -->

    {{-- CAMBIO DE ESTADO --}}
    <div class="panel panel-bordered panel-info">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="voyager-edit"></i> Cambiar Estado</h3>
        </div>
        <div class="panel-body">
            <form action="{{ route('admin.tramites.updateEstado', $tramite) }}" method="POST">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nuevo Estado</label>
                            <select name="estado" class="form-control">
                                <option value="Borrador" {{ $tramite->estado === 'Borrador' ? 'selected' : '' }}>Borrador</option>
                                <option value="Pagado" {{ $tramite->estado === 'Pagado' ? 'selected' : '' }}>Pagado</option>
                                <option value="Observado" {{ $tramite->estado === 'Observado' ? 'selected' : '' }}>Observado</option>
                                <option value="Finalizado" {{ $tramite->estado === 'Finalizado' ? 'selected' : '' }}>Finalizado</option>
                                <option value="Anulado" {{ $tramite->estado === 'Anulado' ? 'selected' : '' }}>Anulado</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-info btn-block">Actualizar Estado</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- AUDITORÍA --}}
    <div class="panel panel-bordered panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="voyager-clock"></i> Registro de Auditoría Interna</h3>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6"><label>OperadorResponsable</label><p class="form-control-static">{{ $tramite->user->name }}</p></div>
                <div class="col-md-3"><label>Fecha y Hora Creación</label><p class="form-control-static">{{ $tramite->created_at->format('d/m/Y H:i') }}</p></div>
                <div class="col-md-3"><label>Última Actualización del Sistema</label><p class="form-control-static">{{ $tramite->updated_at->format('d/m/Y H:i') }}</p></div>
            </div>
        </div>
    </div>
</div>
@stop