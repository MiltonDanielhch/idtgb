@extends('voyager::master')

@section('page_title', 'Crear Trámite Simplificado')

@section('content')
<div class="page-content container-fluid" style="padding: 15px; background-color: #f4f7f6;">
    @include('voyager::alerts')

    <form action="{{ route('admin.tramites.simple.store') }}" method="POST" id="formTramite">
        @csrf

        @if($errors->any())
            <div class="alert alert-danger" style="margin-bottom: 10px;">
                <ul class="mb-0" style="padding-left: 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <style>
            /* --- ARQUITECTURA DE CONTENEDORES (CÓDIGO 3026) --- */
            .layout-viewport {
                display: flex;
                flex-direction: row;
                height: calc(100vh - 120px); /* Ajuste perfecto para el header de Voyager */
                gap: 15px;
                overflow: hidden;
            }
            .col-formulario {
                flex: 1;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                background: #ffffff;
                border-radius: 8px;
                border: 1px solid #e4e7ed;
                overflow: hidden;
            }
            .scroll-body {
                padding: 15px 20px;
                overflow-y: auto;
                flex-grow: 1;
            }
            .footer-fijo {
                padding: 12px 20px;
                background: #fafbfc;
                border-top: 1px solid #edf0f2;
                text-align: right;
            }
            .col-liquidacion {
                width: 320px;
                flex-shrink: 0;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                background: linear-gradient(135deg, #1e1b4b 0%, #311042 100%);
                color: #ffffff !important;
                border-radius: 8px;
                padding: 20px;
                border: 1px solid #3b0764;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
            }

            /* --- ELEMENTOS DE INTERFAZ INTERNOS --- */
            .categoria-card {
                border: 2px solid #e9ecef;
                border-radius: 8px;
                padding: 14px 4px !important;
                min-height: 85px;
                cursor: pointer;
                transition: all 0.2s ease;
                text-align: center;
                background: #fff;
                margin-bottom: 0px;
                display: flex;
                flex-direction: column;
                justify-content: center;
                gap: 6px;
            }
            .categoria-card:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            }
            .categoria-card.selected {
                border-color: #3b82f6;
                background-color: #eff6ff;
            }
            .categoria-tasa {
                font-size: 1.8rem;
                font-weight: 800;
                line-height: 1;
                tracking-spacing: -0.5px;
            }
            .categoria-tasa.linea-directa { color: #10b981; }
            .categoria-tasa.colateral { color: #f59e0b; }
            .categoria-tasa.otros { color: #ef4444; }

            /* Estilos del Desglose de Liquidación */
            .liq-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                padding-bottom: 8px;
                margin-bottom: 12px;
                font-size: 13px;
            }
            .liq-row span { color: #cbd5e1; }
            .liq-row strong { color: #ffffff; font-family: monospace; font-size: 14px; }
            
            .required { color: #ef4444; font-weight: bold; }
            .form-group { margin-bottom: 12px; }
            hr { margin-top: 4px !important; margin-bottom: 12px !important; }
        </style>

        <div class="layout-viewport">
            
            {{-- SECCIÓN IZQUIERDA: Formulario Desplazable --}}
            <div class="col-formulario">
                <div class="scroll-body">
                    <h3 style="margin-top:0; margin-bottom: 15px; font-weight: 800; color: #1e293b; font-size: 18px;">
                        <i class="voyager-file-text text-primary"></i> Crear Trámite Simplificado
                    </h3>

                    <div class="row">
                        {{-- Bloque 1: Tiempos e Impuestos --}}
                        <div class="col-md-6">
                            <h5 class="text-muted font-weight-bold" style="text-transform: uppercase; font-size: 11px; tracking-spacing: 1px;"><i class="voyager-documentation"></i> 1. Tiempos y Categoría del Trámite</h5>
                            <hr>

                            <div class="form-group">
                                <label>Tipo de Transmisión <span class="required">*</span></label>
                                <select name="tipo_transmision_id" id="tipoTransmisionId" class="form-control select2" required>
                                    <option value="">-- Seleccione --</option>
                                    @foreach($tiposTransmision as $tipo)
                                        <option value="{{ $tipo->id }}" data-nombre="{{ \Illuminate\Support\Str::slug($tipo->nombre) }}" {{ old('tipo_transmision_id') == $tipo->id ? 'selected' : '' }}>{{ $tipo->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label style="font-size: 12px; font-weight: bold; color: #475569; margin-bottom: 4px;">Categoría de Tasa <span class="required">*</span></label>
                                <div class="row" id="categoria-selector" style="margin-left:-6px; margin-right:-6px;">
                                    @foreach($categorias as $cat)
                                        <div class="col-xs-4" style="padding: 0 6px;">
                                            <label class="categoria-card" data-categoria="{{ $cat['key'] }}">
                                                <input type="radio" name="categoria_tasa" value="{{ $cat['key'] }}" required class="hidden" style="display:none !important;">
                                                <div style="font-size: 10px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-transform: uppercase; color: #64748b;">{{ $cat['label'] }}</div>
                                                <div class="categoria-tasa {{ $cat['grupo'] }}">{{ $cat['tasa'] }}%</div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6" style="padding-right: 7px;">
                                    <div class="form-group">
                                        <label>F. Transmisión <span class="required">*</span></label>
                                        <input type="date" name="fecha_transmision" class="form-control" value="{{ old('fecha_transmision') }}" required id="fechaTransmision">
                                        <div id="fechaTransmisionError" class="text-danger small" style="display: none; margin-top: 4px; font-size: 11px;">Error de rango</div>
                                    </div>
                                </div>
                                <div class="col-md-6" style="padding-left: 7px;">
                                    <div class="form-group">
                                        <label>F. Presentación</label>
                                        <input type="date" name="fecha_presentacion" class="form-control" value="{{ old('fecha_presentacion', date('Y-m-d')) }}" required readonly style="background-color: #f1f5f9; cursor: not-allowed; height: 34px;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Bloque 2: Sujetos --}}
                        <div class="col-md-6" style="border-left: 1px solid #f1f3f5;">
                            <h5 class="text-muted font-weight-bold" style="text-transform: uppercase; font-size: 11px; tracking-spacing: 1px;"><i class="voyager-user"></i> 2. Datos del Sujeto</h5>
                            <hr>

                            <div class="form-group">
                                <label id="labelSujeto">Persona que Hereda (Adquirente) <span class="required">*</span></label>
                                <div class="input-group" style="display: flex; width: 100%;">
                                    <div style="flex-grow: 1;">
                                        <select name="adquirente_id" class="form-control select2-ajax" style="width: 100% !important;"
                                            data-ajax--url="{{ route('admin.tramites.simple.ajax.persons') }}"
                                            data-placeholder="Buscar persona por CI/Nombre..." required>
                                            <option value="">-- Seleccione o cree nueva --</option>
                                        </select>
                                    </div>
                                    <span class="input-group-btn" style="width: auto;">
                                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-create-person" style="margin-top: 0px; height: 34px; padding: 6px 12px;">
                                            <i class="voyager-plus"></i>
                                        </button>
                                    </span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Tipo de Contribuyente <span class="required">*</span></label>
                                <select name="tipo_contribuyente" id="tipoContribuyente" class="form-control" required>
                                    <option value="Natural" {{ old('tipo_contribuyente', 'Natural') === 'Natural' ? 'selected' : '' }}>Persona Natural (50 UFV multa)</option>
                                    <option value="Jurídica" {{ old('tipo_contribuyente') === 'Jurídica' ? 'selected' : '' }}>Persona Jurídica (100 UFV multa)</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6" style="padding-right: 7px;">
                                    <div class="form-group">
                                        <label>Base Imponible (Bs) <span class="required">*</span></label>
                                        <input type="number" name="base_imponible" class="form-control" step="0.01" min="0" id="baseImponible" value="{{ old('base_imponible') }}" required style="font-family: monospace; font-weight: bold;">
                                    </div>
                                </div>
                                <div class="col-md-6" style="padding-left: 7px;">
                                    <div class="form-group">
                                        <label>% Participación <span class="required">*</span></label>
                                        <input type="number" name="porcentaje" class="form-control" value="{{ old('porcentaje', 100) }}" min="1" max="100" id="porcentaje" required style="font-family: monospace; font-weight: bold;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Observaciones optimizadas abajo --}}
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group" style="margin-top: 5px; margin-bottom: 0;">
                                <label style="font-size: 12px; color: #475569;">Observaciones de Ventanilla</label>
                                <textarea name="observaciones" class="form-control" rows="1" style="resize: none; height: 38px;" placeholder="Ej. Documentos de descargo en orden..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- AREA FIJA INFERIOR DE ACCIÓN --}}
                <div class="footer-fijo">
                    <a href="{{ route('admin.tramites.index') }}" class="btn btn-default" style="margin-bottom:0;">
                        <i class="voyager-angle-left"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-success font-weight-bold" style="margin-bottom:0; background-color:#10b981; border-color:#10b981;">
                        <i class="voyager-check"></i> Guardar Trámite
                    </button>
                </div>
            </div>

            {{-- SECCIÓN DERECHA: Panel Consolidado de Liquidación (Fijo) --}}
            <div class="col-liquidacion">
                <div>
                    <h5 class="mt-0" style="margin-top: 0; margin-bottom: 20px; font-weight: 800; letter-spacing: 0.5px; border-bottom: 1px solid rgba(255,255,255,0.15); padding-bottom: 10px; text-transform: uppercase; font-size: 12px; color: #a5b4fc;">
                        <i class="voyager-check-circle"></i> Liquidación (Ley 812)
                    </h5>
                    
                    <div id="desgloseValores" style="display: none;">
                        <div class="liq-row">
                            <span>Tributo Actualizado:</span>
                            <strong id="idtgbBase">Bs. 0.00</strong>
                        </div>
                        <div class="liq-row">
                            <span>Intereses (Mora):</span>
                            <strong id="recargoMora">Bs. 0.00</strong>
                        </div>
                        <div class="liq-row">
                            <span>Multa IDF:</span>
                            <strong id="multaIdf">Bs. 0.00</strong>
                        </div>

                        <div style="margin-top: 25px; background: rgba(0,0,0,0.2); padding: 10px; border-radius: 6px; font-size: 11px; border: 1px solid rgba(255,255,255,0.05);">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;"><span>Trámite:</span><span id="nroTramite" style="font-weight: bold; color:#fff;">-</span></div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;"><span>Categoría:</span><span id="categoria" style="font-weight: bold; color:#fff;">-</span></div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;"><span>UFV Venc:</span><span id="ufvVenc" style="font-weight: bold; color:#f59e0b;">-</span></div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;"><span>UFV Pago:</span><span id="ufvPago" style="font-weight: bold; color:#10b981;">-</span></div>
                            <div style="display: flex; justify-content: space-between;"><span>Días Mora:</span><span id="diasMora" style="font-weight: bold; color:#f43f5e;">-</span></div>
                        </div>
                    </div>

                    <div id="panelVacio" class="text-center" style="color: rgba(255,255,255,0.4); padding-top: 40px; font-size: 12px;">
                        <i class="voyager-calculator" style="font-size: 28px; display:block; margin-bottom:10px;"></i>
                        Introduzca montos y fechas para proyectar la liquidación de caja.
                    </div>
                </div>

                <div style="border-top: 1px solid rgba(255,255,255,0.15); margin-top: 20px; padding-top: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: baseline;">
                        <span style="font-size: 10px; font-weight: bold; text-transform: uppercase; color: #a5b4fc; tracking-spacing: 0.5px;">Monto Total</span>
                        <span id="montoFinal" style="font-size: 26px; font-weight: 900; font-family: monospace; tracking-spacing: -0.5px;">Bs. 0.00</span>
                    </div>
                    <div style="text-align: center; font-size: 9px; color: rgba(255,255,255,0.3); margin-top: 15px;">
                        GAD BENI — Gestión Tributaria IDTGB v1.7
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>
@stop

@include('partials.modal-registerPerson')

@push('javascript')
<script>
$(document).ready(function() {
    // Inicializar Select2 AJAX
    $('.select2-ajax').select2({
        ajax: {
            url: function() { return $(this).data('ajax--url'); },
            dataType: 'json',
            delay: 250,
            data: function(params) { return { q: params.term }; },
            processResults: function(data) { return { results: data.results }; }
        },
        minimumInputLength: 2
    });

    // 🎯 REGLA DE DINAMISMO: Cambiar etiquetas según el tipo de transmisión en tiempo real
    $('#tipoTransmisionId').on('change', function() {
        const optionSelected = $(this).find('option:selected').text().toLowerCase();
        
        if (optionSelected.includes('vivo') || optionSelected.includes('donac')) {
            $('#labelSujeto').html('Persona que Recibe Donación (Donatario) <span class="required">*</span>');
        } else {
            $('#labelSujeto').html('Persona que Hereda (Adquirente) <span class="required">*</span>');
        }
        calcular();
    });

    // Validación interactiva de fechas
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

    // Cambios rápidos en las tarjetas de Categorías
    $('.categoria-card').click(function() {
        $('.categoria-card').removeClass('selected');
        $(this).addClass('selected');
        $(this).find('input').prop('checked', true);
        calcular();
    });

    // Motor de cálculo vía AJAX conectado a tu backend
    function calcular() {
        const baseImponible = parseFloat($('#baseImponible').val()) || 0;
        const categoriaTasa = $('input[name="categoria_tasa"]:checked').val();
        const tipoTransmisionId = $('#tipoTransmisionId').val();
        const fechaTransmision = $('input[name="fecha_transmision"]').val();
        const porcentaje = parseFloat($('#porcentaje').val()) || 100;
        const tipoContribuyente = $('#tipoContribuyente').val();

        if (baseImponible > 0 && categoriaTasa && tipoTransmisionId && fechaTransmision) {
            $.ajax({
                url: '{{ route('admin.tramites.simple.ajax.calculate') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    base_imponible: baseImponible,
                    categoria_tasa: categoriaTasa,
                    tipo_transmision_id: tipoTransmisionId,
                    fecha_transmision: fechaTransmision,
                    porcentaje: porcentaje,
                    tipo_contribuyente: tipoContribuyente
                },
                success: function(response) {
                    if (response.success) {
                        const resultados = response.resultados;
                        
                        $('#panelVacio').hide();
                        $('#desgloseValores').fadeIn();

                        $('#idtgbBase').text('Bs. ' + resultados.tributo_actualizado.toFixed(2));
                        $('#recargoMora').text('Bs. ' + resultados.interes.toFixed(2));
                        $('#multaIdf').text('Bs. ' + resultados.multa_idf.toFixed(2));
                        $('#montoFinal').text('Bs. ' + (Math.round(resultados.final * 2) / 2).toFixed(2));
                        
                        $('#nroTramite').text(resultados.nro_tramite);
                        $('#categoria').text(resultados.categoria_tasa == 1 ? 'Línea Directa' : resultados.categoria_tasa == 10 ? 'Colateral' : 'Otros');
                        $('#ufvVenc').text(resultados.ufv_vencimiento.toFixed(5));
                        $('#ufvPago').text(resultados.ufv_pago.toFixed(5));
                        $('#diasMora').text(resultados.dias_mora);
                    }
                },
                error: function(xhr) {
                    console.error('Error en el núcleo de cálculo:', xhr.responseJSON?.error);
                }
            });
        } else {
            // Resetear vista en caso de datos incompletos
            $('#desgloseValores').hide();
            $('#panelVacio').show();
            $('#montoFinal').text('Bs. 0.00');
        }
    }

    // Eventos disparadores del cálculo automático
    $('#baseImponible, input[name="categoria_tasa"], #fechaTransmision, #porcentaje, #tipoContribuyente').on('change', calcular);
    $('#baseImponible, #porcentaje').on('input', calcular);
});
</script>
@endpush