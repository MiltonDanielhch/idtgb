{{-- resources/views/admin/tramites/wizard/create_step_1.blade.php --}}
@extends('admin.tramites.wizard.layout')

@section('page_title', 'Agregar Trámite - Paso 1: Datos Generales')

@section('wizard-content')
<div class="panel panel-bordered">
    <form action="{{ route('admin.tramites.wizard.post.step1') }}" method="POST">
        @csrf
        <div class="panel-body">
            <h4 class="text-muted"><i class="voyager-documentation"></i> {{ $step_title }}</h4>
            <p class="text-hint">Inicie el registro capturando la información básica del documento de transmisión.</p>
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
                {{-- Columna Izquierda: Identificación --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="nro_tramite">Número de Trámite / Hoja de Ruta <span class="required">*</span></label>
                        <input type="text" name="nro_tramite" class="form-control"
                               value="{{ old('nro_tramite', $wizardData['step1']['nro_tramite'] ?? '') }}"
                               placeholder="Ej: 2026-X123" required>
                    </div>

                    <div class="form-group">
                        <label for="tipo_transmision_id">Tipo de Transmisión <span class="required">*</span></label>
                        <select name="tipo_transmision_id" class="form-control select2" required>
                            <option value="">-- Seleccione el tipo --</option>
                            @foreach($tiposTransmision as $tipo)
                                <option value="{{ $tipo->id }}"
                                    {{ (old('tipo_transmision_id', $wizardData['step1']['tipo_transmision_id'] ?? '') == $tipo->id) ? 'selected' : '' }}>
                                    {{ $tipo->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Columna Derecha: Fechas y Valores --}}
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="fecha_presentacion">Fecha de Presentación <span class="required">*</span></label>
                        <input type="date" name="fecha_presentacion" class="form-control"
                               value="{{ old('fecha_presentacion', $wizardData['step1']['fecha_presentacion'] ?? date('Y-m-d')) }}" required>
                        <small class="text-muted">Fecha de ingreso al GAD Beni.</small>
                    </div>

                    <div class="form-group">
                        <label for="valor_declarado">Valor Declarado (Bs.) <span class="required">*</span></label>
                        <input type="number" step="0.01" name="valor_declarado" class="form-control"
                               value="{{ old('valor_declarado', $wizardData['step1']['valor_declarado'] ?? '') }}"
                               placeholder="0.00" required>
                        <small class="text-muted">Monto según Minuta o Testimonio.</small>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label for="fecha_transmision">Fecha de Transmisión <span class="required">*</span></label>
                        <input type="date" name="fecha_transmision" class="form-control"
                               value="{{ old('fecha_transmision', $wizardData['step1']['fecha_transmision'] ?? '') }}" required>
                        <small class="text-muted">Fecha del fallecimiento o minuta.</small>
                    </div>

                    <div class="form-group">
                        <label for="base_imponible">Base Imponible Sugerida (Bs.)</label>
                        <input type="number" step="0.01" name="base_imponible" class="form-control"
                               value="{{ old('base_imponible', $wizardData['step1']['base_imponible'] ?? '') }}"
                               placeholder="Se recalculará en el paso 7">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="observaciones">Observaciones Iniciales</label>
                        <textarea name="observaciones" class="form-control" rows="3">{{ old('observaciones', $wizardData['step1']['observaciones'] ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel-footer">
            <a href="{{ route('admin.tramites.index') }}" class="btn btn-default">Cancelar</a>
            <button type="submit" class="btn btn-primary pull-right">
                Siguiente: Personas <i class="voyager-angle-right"></i>
            </button>
            <div style="clear:both;"></div>
        </div>
    </form>
</div>
@endsection

@push('javascript')
<script>
    $(document).ready(function() {
        $('.select2').select2();

        // Lógica de Sintonía: Autocompletar Base Imponible si está vacía
        $('input[name="valor_declarado"]').on('input', function() {
            let valor = $(this).val();
            if($('input[name="base_imponible"]').val() == '') {
                $('input[name="base_imponible"]').val(valor);
            }
        });
    });
</script>
@endpush
