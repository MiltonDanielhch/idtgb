{{-- resources/views/admin/tramites/wizard/create_step_1.blade.php --}}
@extends('admin.tramites.wizard.layout')

@section('page_title', 'Agregar Trámite - Paso 1')

@section('wizard-content')
<form action="{{ route('admin.tramites.wizard.post.step1') }}" method="POST">
    @csrf
    <div class="panel panel-bordered">
        <div class="panel-body">
            <h4 class="text-muted">{{ $step_title }}</h4>
            <hr>

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row">
                {{-- Número de trámite --}}
                <div class="col-md-4">
                    <label>Nro Trámite <span class="required">*</span></label>
                    <input type="text" name="nro_tramite" class="form-control @error('nro_tramite') is-invalid @enderror"
                           value="{{ old('nro_tramite', $data['nro_tramite'] ?? '') }}"
                           required maxlength="15" placeholder="Ej: T-2025-0001">
                    @error('nro_tramite')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                    <small class="text-muted">Número único de trámite.</small>
                </div>

                {{-- Fecha de presentación --}}
                <div class="col-md-4">
                    <label>Fecha Presentación <span class="required">*</span></label>
                    <input type="date" name="fecha_presentacion" class="form-control @error('fecha_presentacion') is-invalid @enderror"
                           value="{{ old('fecha_presentacion', $data['fecha_presentacion'] ?? today()->format('Y-m-d')) }}"
                           required>
                    @error('fecha_presentacion')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Fecha de transmisión --}}
                <div class="col-md-4">
                    <label>Fecha Transmisión <span class="required">*</span></label>
                    <input type="date" name="fecha_transmision" class="form-control @error('fecha_transmision') is-invalid @enderror"
                           value="{{ old('fecha_transmision', $data['fecha_transmision'] ?? '') }}"
                           required>
                    @error('fecha_transmision')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="row" style="margin-top: 15px;">
                {{-- Tipo de transmisión --}}
                <div class="col-md-4">
                    <label>Tipo Transmisión <span class="required">*</span></label>
                    <select name="tipo_transmision_id" class="form-control select2 @error('tipo_transmision_id') is-invalid @enderror" required>
                        <option value="">Seleccione...</option>
                        @foreach($tipos as $tipo)
                            <option value="{{ $tipo->id }}"
                                {{ old('tipo_transmision_id', $data['tipo_transmision_id'] ?? '') == $tipo->id ? 'selected' : '' }}>
                                {{ $tipo->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('tipo_transmision_id')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Valor declarado --}}
                <div class="col-md-4">
                    <label>Valor Declarado (Bs) <span class="required">*</span></label>
                    <input type="number" step="0.01" min="0" name="valor_declarado"
                           class="form-control @error('valor_declarado') is-invalid @enderror"
                           value="{{ old('valor_declarado', $data['valor_declarado'] ?? '') }}"
                           required placeholder="0.00">
                    @error('valor_declarado')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                    <small class="text-muted">Monto según documento de transferencia.</small>
                </div>

                {{-- Base imponible --}}
                <div class="col-md-4">
                    <label>Base Imponible (Bs) <span class="required">*</span></label>
                    <input type="number" step="0.01" min="0" name="base_imponible"
                           class="form-control @error('base_imponible') is-invalid @enderror"
                           value="{{ old('base_imponible', $data['base_imponible'] ?? '') }}"
                           required placeholder="0.00">
                    @error('base_imponible')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                    <small class="text-muted">Valor para cálculo del impuesto.</small>
                </div>
            </div>

            <div class="row" style="margin-top: 15px;">
                {{-- Observaciones --}}
                <div class="col-md-12">
                    <label>Observaciones</label>
                    <textarea name="observaciones" class="form-control @error('observaciones') is-invalid @enderror"
                              rows="3" placeholder="Anotaciones internas sobre el trámite...">{{ old('observaciones', $data['observaciones'] ?? '') }}</textarea>
                    @error('observaciones')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
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
            e.target.value = value;
        });

        // Inicializar Select2
        $('.select2').select2({
            theme: 'bootstrap'
        });
    });
</script>
@endpush
