@extends('voyager::master')

@section('page_title', 'Registrar Pago - Trámite '.$tramite->nro_tramite)

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    <form action="{{ route('admin.tramites.pagos.store', $tramite) }}" method="POST">
        @csrf

        <div class="panel panel-bordered panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="voyager-dollar"></i> Registrar Pago - Trámite {{ $tramite->nro_tramite }}
                </h3>
            </div>

            <div class="panel-body">
                <div class="row">
                    {{-- Fecha de pago --}}
                    <div class="col-md-3">
                        <label>Fecha de pago <span class="required">*</span></label>
                        <input type="datetime-local" name="fecha_pago" class="form-control"
                               value="{{ old('fecha_pago', now()->format('Y-m-d\TH:i')) }}" required>
                        @error('fecha_pago') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    {{-- Monto --}}
                    <div class="col-md-3">
                        <label>Monto (Bs) <span class="required">*</span></label>
                        @php
                            $monto_redondeado = round($tramite->monto_final * 2) / 2;
                        @endphp
                        <input type="number" step="0.01" min="0.01" name="monto" class="form-control"
                               value="{{ old('monto', $monto_redondeado) }}" required>
                        <small class="text-muted">Total a pagar: Bs. {{ number_format($monto_redondeado, 2) }}</small>
                        @error('monto') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    {{-- Banco --}}
                    <div class="col-md-3">
                        <label>Banco</label>
                        <select name="banco" class="form-control">
                            <option value="">-- Seleccione --</option>
                            @foreach($bancos as $b)
                                <option value="{{ $b }}" {{ old('banco') == $b ? 'selected' : '' }}>{{ $b }}</option>
                            @endforeach
                        </select>
                        @error('banco') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    {{-- Nro operación --}}
                    <div class="col-md-3">
                        <label>Nro operación</label>
                        <input type="text" name="nro_operacion" class="form-control"
                               value="{{ old('nro_operacion') }}" maxlength="25">
                        @error('nro_operacion') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                </div>
            </div>
            <div class="panel-footer text-right">
                <a href="{{ route('admin.tramites.pagos.index', $tramite) }}" class="btn btn-default">
                    <i class="voyager-angle-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="voyager-check"></i> Registrar Pago
                </button>
            </div>
        </div>
    </form>
</div>
@stop
