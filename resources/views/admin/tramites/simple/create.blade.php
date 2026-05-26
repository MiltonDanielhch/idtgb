@extends('voyager::master')

@section('page_title', 'Crear Trámite Simplificado')

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    <form action="{{ route('admin.tramites.simple.store') }}" method="POST">
        @csrf

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="panel panel-bordered panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="voyager-file-text"></i> Crear Trámite Simplificado
                </h3>
            </div>

            <div class="panel-body">
                <style>
                    .categoria-card {
                        border: 2px solid #e9ecef;
                        border-radius: 8px;
                        padding: 15px;
                        cursor: pointer;
                        transition: all 0.2s ease;
                        text-align: center;
                        background: #fff;
                    }
                    .categoria-card:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                    }
                    .categoria-card.selected {
                        border-color: #007bff;
                        background-color: #e7f3ff;
                    }
                    .categoria-tasa {
                        font-size: 1.8rem;
                        font-weight: bold;
                        line-height: 1;
                    }
                    .categoria-tasa.linea-directa { color: #28a745; }
                    .categoria-tasa.colateral { color: #ffc107; }
                    .categoria-tasa.otros { color: #dc3545; }
                    .result-card {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white !important;
                        border-radius: 10px;
                        padding: 20px;
                        margin-top: 20px;
                        border: 2px solid #5a67d8;
                        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
                    }
                    .result-card h5 {
                        color: white !important;
                        font-weight: bold;
                    }
                    .result-card table {
                        color: white !important;
                    }
                    .result-card td {
                        color: white !important;
                    }
                    .result-card strong {
                        color: white !important;
                    }
                    .result-card span {
                        color: white !important;
                    }
                </style>

                <div class="row">
                    {{-- Columna 1: Datos Técnicos y Fechas --}}
                    <div class="col-md-4">
                        <h4 class="text-muted mb-3"><i class="voyager-documentation"></i> Datos del Trámite</h4>

                        <div class="form-group">
                            <label>Tipo de Transmisión <span class="required">*</span></label>
                            <select name="tipo_transmision_id" class="form-control select2" required>
                                <option value="">-- Seleccione --</option>
                                @foreach($tiposTransmision as $tipo)
                                    <option value="{{ $tipo->id }}" {{ old('tipo_transmision_id') == $tipo->id ? 'selected' : '' }}>{{ $tipo->nombre }}</option>
                                @endforeach
                            </select>
                            @error('tipo_transmision_id') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label>Fecha de Transmisión <span class="required">*</span></label>
                            <input type="date" name="fecha_transmision" class="form-control" value="{{ old('fecha_transmision') }}" required id="fechaTransmision">
                            <div id="fechaTransmisionError" class="text-danger small" style="display: none;">La fecha de transmisión debe ser anterior o igual a la fecha de presentación</div>
                            @error('fecha_transmision') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label>Fecha de Presentación <span class="required">*</span></label>
                            <input type="date" name="fecha_presentacion" class="form-control" value="{{ old('fecha_presentacion', date('Y-m-d')) }}" required readonly>
                            <small class="text-muted">Fecha actual del sistema</small>
                            @error('fecha_presentacion') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Columna 2: Adquirente --}}
                    <div class="col-md-4" style="border-left: 1px solid #f1f1f1; border-right: 1px solid #f1f1f1;">
                        <h4 class="text-muted mb-3"><i class="voyager-user"></i> Sujeto</h4>

                        <div class="form-group">
                            <label>Persona que Hereda (Adquirente) <span class="required">*</span></label>
                            <div class="input-group" style="display: flex; width: 100%;">
                                <div style="flex-grow: 1;">
                                    <select name="adquirente_id" class="form-control select2-ajax" style="width: 100% !important;"
                                        data-ajax--url="{{ route('admin.tramites.simple.ajax.persons') }}"
                                        data-placeholder="Buscar persona..." required>
                                        <option value="">-- Seleccione o cree nueva --</option>
                                    </select>
                                </div>
                                <span class="input-group-btn" style="width: auto;">
                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-create-person" style="margin-top: 0px; height: 34px;">
                                        <i class="voyager-plus"></i>
                                    </button>
                                </span>
                            </div>
                            @error('adquirente_id') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label>Parentesco <span class="required">*</span></label>
                            <select name="parentesco_id" class="form-control select2" id="parentescoSelect" style="width: 100% !important;" required>
                                <option value="">-- Seleccione --</option>
                                @foreach($parentescos as $parentesco)
                                    <option value="{{ $parentesco->id }}" data-categoria="{{ $parentesco->categoria_tasa }}" {{ old('parentesco_id') == $parentesco->id ? 'selected' : '' }}>
                                        {{ $parentesco->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('parentesco_id') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Columna 3: Montos, Categorías y Liquidación --}}
                    <div class="col-md-4">
                        <h4 class="text-muted mb-3"><i class="voyager-calculator"></i> Liquidación</h4>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Base Imponible (Bs) <span class="required">*</span></label>
                                    <input type="number" name="base_imponible" class="form-control" step="0.01" min="0" id="baseImponible" value="{{ old('base_imponible') }}" required>
                                    @error('base_imponible') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>% Participación <span class="required">*</span></label>
                                    <input type="number" name="porcentaje" class="form-control" value="{{ old('porcentaje', 100) }}" min="1" max="100" id="porcentaje" required>
                                    @error('porcentaje') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Categoría de Tasa:</label>
                            <div class="row g-2" id="categoria-selector">
                                @foreach($categorias as $cat)
                                    <div class="col-md-4" style="padding: 0 4px;">
                                        <label class="categoria-card p-2" data-categoria="{{ $cat['key'] }}" style="margin-bottom: 5px; padding: 8px !important;">
                                            <input type="radio" name="categoria_tasa" value="{{ $cat['key'] }}" required class="d-none">
                                            <div class="small fw-bold" style="font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $cat['label'] }}</div>
                                            <div class="categoria-tasa {{ $cat['grupo'] }}" style="font-size: 1.2rem;">{{ $cat['tasa'] }}%</div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Resultado del cálculo integrado directamente --}}
                        <div class="result-card" id="resultCard" style="display: none; margin-top: 10px; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px; border: 2px solid #5a67d8; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);">
                            <h5 class="mt-0" style="margin-top: 0; color: white; font-weight: bold;"><i class="voyager-check-circle"></i> Estimación</h5>
                            <table class="table table-condensed" style="margin-bottom: 0; color: white; background: transparent;">
                                <tr style="border: none; color: white;"><td style="padding: 2px 0; border: none; color: white;">IDTGB Base:</td><td class="text-right" style="padding: 2px 0; border: none; color: white;"><strong id="idtgbBase" style="color: white;">Bs. 0.00</strong></td></tr>
                                <tr style="color: white;"><td style="padding: 2px 0; border: none; color: white;">Recargo/Mora:</td><td class="text-right" style="padding: 2px 0; border: none; color: white;"><strong id="recargoMora" style="color: white;">Bs. 0.00</strong></td></tr>
                                <tr style="font-size: 1.1em; border-top: 1px solid rgba(255,255,255,0.3); color: white;"><td style="padding: 4px 0; border: none; color: white;"><strong style="color: white;">Monto Final:</strong></td><td class="text-right" style="padding: 4px 0; border: none; color: white;"><strong id="montoFinal" style="color: white;">Bs. 0.00</strong></td></tr>
                                <tr style="font-size: 0.85em; opacity: 0.8; color: white;"><td style="padding: 2px 0; border: none; color: white;">UFV:</td><td class="text-right" style="padding: 2px 0; border: none; color: white;"><span id="ufvAplicada" style="color: white;">1.00000</span></td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-3">
                    <label>Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="3"></textarea>
                </div>
            </div>

            <div class="panel-footer text-right">
                <a href="{{ route('admin.tramites.index') }}" class="btn btn-default">
                    <i class="voyager-angle-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="voyager-check"></i> Guardar Trámite
                </button>
            </div>
        </div>
    </form>
</div>
@stop

{{-- Modal para crear persona --}}
@include('partials.modal-registerPerson')

@push('javascript')
<script>
$(document).ready(function() {
    // Inicializar Select2 AJAX
    $('.select2-ajax').select2({
        ajax: {
            url: function() {
                return $(this).data('ajax--url');
            },
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { q: params.term };
            },
            processResults: function(data) {
                return { results: data.results };
            }
        },
        minimumInputLength: 2
    });

    // Validación en tiempo real de fecha de transmisión
    $('#fechaTransmision').on('change', function() {
        const fechaTransmision = $(this).val();
        const fechaPresentacion = $('input[name="fecha_presentacion"]').val();

        if (fechaTransmision && fechaPresentacion) {
            if (new Date(fechaTransmision) > new Date(fechaPresentacion)) {
                $('#fechaTransmisionError').show();
                $(this).addClass('is-invalid');
            } else {
                $('#fechaTransmisionError').hide();
                $(this).removeClass('is-invalid');
            }
        }
    });

    // Validación inicial al cargar la página
    $(document).ready(function() {
        const fechaTransmision = $('#fechaTransmision').val();
        const fechaPresentacion = $('input[name="fecha_presentacion"]').val();

        if (fechaTransmision && fechaPresentacion) {
            if (new Date(fechaTransmision) > new Date(fechaPresentacion)) {
                $('#fechaTransmisionError').show();
                $('#fechaTransmision').addClass('is-invalid');
            }
        }
    });

    // Categorías rápidas
    $('.categoria-card').click(function() {
        const categoria = $(this).data('categoria');
        $('.categoria-card').removeClass('selected');
        $(this).addClass('selected');
        $(this).find('input').prop('checked', true);

        // Seleccionar primer parentesco de esa categoría
        $('#parentescoSelect option[data-categoria="' + categoria + '"]').prop('selected', true);
        $('#parentescoSelect').trigger('change');
        calcular();
    });

    // Cálculo automático
    function calcular() {
        const baseImponible = parseFloat($('#baseImponible').val()) || 0;
        const parentescoId = $('#parentescoSelect').val();
        const tipoTransmisionId = $('select[name="tipo_transmision_id"]').val();
        const fechaTransmision = $('input[name="fecha_transmision"]').val();
        const porcentaje = parseFloat($('#porcentaje').val()) || 100;

        if (baseImponible > 0 && parentescoId && tipoTransmisionId && fechaTransmision) {
            $.ajax({
                url: '{{ route('admin.tramites.simple.ajax.calculate') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    base_imponible: baseImponible,
                    parentesco_id: parentescoId,
                    tipo_transmision_id: tipoTransmisionId,
                    fecha_transmision: fechaTransmision,
                    porcentaje: porcentaje
                },
                success: function(response) {
                    if (response.success) {
                        const resultados = response.resultados;
                        $('#idtgbBase').text('Bs. ' + resultados.idtgb_base.toFixed(2));
                        $('#recargoMora').text('Bs. ' + (resultados.mantenimiento_valor + resultados.interes + resultados.multa_idf).toFixed(2));
                        $('#montoFinal').text('Bs. ' + resultados.final.toFixed(2));
                        $('#ufvAplicada').text(resultados.ufv_pago.toFixed(5));
                        $('#resultCard').fadeIn();
                    }
                },
                error: function(xhr) {
                    console.error('Error en cálculo:', xhr.responseJSON?.error);
                }
            });
        }
    }

    // Calcular al cambiar campos relevantes
    $('#baseImponible, #parentescoSelect, select[name="tipo_transmision_id"], input[name="fecha_transmision"], #porcentaje').on('change', calcular);
    $('#baseImponible, #porcentaje').on('input', calcular);
});
</script>
@endpush
