@extends('voyager::master')

@section('content')
<div class="page-content edit container-fluid">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Pagar Trámite IDTGB</h3>
                </div>
                <div class="panel-body">
                    <table class="table">
                        <tr><th>Nro Trámite</th><td>{{ $tramite->nro_tramite }}</td></tr>
                        <tr><th>Monto Final</th><td><strong>Bs {{ number_format($tramite->monto_final, 2) }}</strong></td></tr>
                        <tr><th>Vencimiento</th><td>{{ $tramite->fecha_vencimiento->format('d/m/Y') }}</td></tr>
                    </table>

                    @if($tramite->estado == 'Pagado')
                        <div class="alert alert-success">
                            <i class="voyager-check"></i> Este trámite **ya está pagado**.
                        </div>
                        <a href="{{ route('admin.pago.comprobante', $tramite->pago) }}" class="btn btn-primary">
                            <i class="voyager-download"></i> Descargar Comprobante
                        </a>
                    @else
                        <form action="{{ route('admin.tramites.pago.store', $tramite) }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <label>Fecha de Pago</label>
                                    <input type="datetime-local" name="fecha_pago" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label>Banco</label>
                                    <input type="text" name="banco" class="form-control" placeholder="Banco nombre" required>
                                </div>
                            </div>
                            <div class="row" style="margin-top:15px;">
                                <div class="col-md-6">
                                    <label>Nro Operación</label>
                                    <input type="text" name="nro_operacion" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label>Código de Barras (auto)</label>
                                    <input type="text" name="codigo_barras" class="form-control" value="{{ 'IDTGB-'.time() }}" readonly>
                                </div>
                            </div>
                            <div class="panel-footer text-right" style="margin-top:20px;">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="voyager-check"></i> Registrar Pago
                                </button>
                                <a href="{{ route('admin.tramites.index') }}" class="btn btn-default">Cancelar</a>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@stop
