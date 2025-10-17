@extends('admin.tramites.wizard.layout')

@section('page_title', 'Agregar Trámite - Paso 1')

@section('wizard-content')
<form action="{{ route('admin.tramites.wizard.post.step1') }}"
      method="POST">
    @csrf
    <div class="panel panel-bordered">
        <div class="panel-body">
            <h4 class="text-muted">Datos Generales del Trámite</h4>
            <hr>
            <div class="row">
                {{-- Número de trámite --}}
                <div class="col-md-4">
                    <label>Nro Trámite <span class="required">*</span></label>
                    <input type="text" name="nro_tramite" class="form-control"
                           value="{{ old('nro_tramite', $tramite->nro_tramite) }}"
                           required maxlength="15" placeholder="Ej: A-01-2025-0001">
                    <small class="text-muted">Formato: Letra-Consecutivo-Año-Correlativo.</small>
                </div>

                {{-- Fecha de presentación --}}
                <div class="col-md-4">
                    <label>Fecha Presentación <span class="required">*</span></label>
                    <input type="date" name="fecha_presentacion" class="form-control"
                           value="{{ old('fecha_presentacion', $tramite->fecha_presentacion ? $tramite->fecha_presentacion->format('Y-m-d') : today()->format('Y-m-d')) }}"
                           required>
                </div>

                {{-- Fecha de transmisión --}}
                <div class="col-md-4">
                    <label>Fecha Transmisión <span class="required">*</span></label>
                    <input type="date" name="fecha_transmision" class="form-control"
                           value="{{ old('fecha_transmision', $tramite->fecha_transmision ? $tramite->fecha_transmision->format('Y-m-d') : '') }}"
                           required>
                </div>
            </div>

            <div class="row" style="margin-top: 15px;">
                {{-- Tipo de transmisión --}}
                <div class="col-md-4">
                    <label>Tipo Transmisión <span class="required">*</span></label>
                    <select name="tipo_transmision_id" class="form-control select2" required>
                        <option value="">Elija...</option>
                        @foreach($tipos as $t)
                            <option value="{{ $t->id }}"
                                {{ old('tipo_transmision_id', $tramite->tipo_transmision_id) == $t->id ? 'selected' : '' }}>
                                {{ $t->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Valor declarado --}}
                <div class="col-md-4">
                    <label>Valor Declarado (Bs) <span class="required">*</span></label>
                    <input type="number" step="0.01" min="0" name="valor_declarado" class="form-control"
                           value="{{ old('valor_declarado', $tramite->valor_declarado) }}"
                           required placeholder="Ej: 520000.00">
                    <small class="text-muted">Monto de la transferencia según el documento.</small>
                </div>

                {{-- Base imponible --}}
                <div class="col-md-4">
                    <label>Base Imponible (Bs) <span class="required">*</span></label>
                    <input type="number" step="0.01" min="0" name="base_imponible" class="form-control"
                           value="{{ old('base_imponible', $tramite->base_imponible) }}"
                           required placeholder="Ej: 500000.00">
                    <small class="text-muted">Valor para el cálculo del impuesto (el mayor entre el declarado y el avalúo fiscal).</small>
                </div>
            </div>

            <div class="row" style="margin-top: 15px;">
                {{-- Observaciones --}}
                <div class="col-md-12">
                    <label>Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="3"
                              placeholder="Anotaciones internas sobre el trámite.">{{ old('observaciones', $tramite->observaciones) }}</textarea>
                </div>
            </div>
        </div>

        <div class="panel-footer text-right">
            <a href="{{ route('admin.tramites.wizard.cancel') }}" class="btn btn-danger">
                <i class="voyager-x"></i> Cancelar Proceso
            </a>
            <button type="submit" class="btn btn-primary">
                Siguiente <i class="voyager-angle-right"></i>
            </button>
        </div>
    </div>
</form>
@endsection

@push('javascript')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const nroTramiteInput = document.querySelector('input[name="nro_tramite"]');

        nroTramiteInput.addEventListener('input', function (e) {
            let value = e.target.value.toUpperCase().replace(/[^A-Z0-9-]/g, '');
            // No auto-formatear, solo validar caracteres
            e.target.value = value;
        });
    });
</script>
@endpush
