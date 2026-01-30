@extends('admin.tramites.wizard.layout')

@section('page_title', 'Agregar Trámite - Paso 7: Resumen')

@section('wizard-content')
<div class="panel panel-bordered">
    <div class="panel-body">
        <h4 class="text-muted">{{ $step_title }}</h4>
        <hr>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row">
            <div class="col-md-12">
                <h5><i class="voyager-document"></i> Datos del Trámite</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Nro. Trámite:</strong> {{ $wizardData['step1']['nro_tramite'] ?? 'N/A' }}</p>
                        <p><strong>Fecha Presentación:</strong>
                            {{ isset($wizardData['step1']['fecha_presentacion']) ? \Carbon\Carbon::parse($wizardData['step1']['fecha_presentacion'])->format('d/m/Y') : 'N/A' }}
                        </p>
                        <p><strong>Fecha Transmisión:</strong>
                            {{ isset($wizardData['step1']['fecha_transmision']) ? \Carbon\Carbon::parse($wizardData['step1']['fecha_transmision'])->format('d/m/Y') : 'N/A' }}
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Tipo Transmisión:</strong>
                            {{ $tipoTransmision->nombre ?? 'N/A' }}
                        </p>
                        <p><strong>Valor Declarado:</strong>
                            {{ isset($wizardData['step1']['valor_declarado']) ? 'Bs. ' . number_format($wizardData['step1']['valor_declarado'], 2) : 'N/A' }}
                        </p>
                        <p><strong>Base Imponible:</strong>
                            {{ isset($wizardData['step1']['base_imponible']) ? 'Bs. ' . number_format($wizardData['step1']['base_imponible'], 2) : 'N/A' }}
                        </p>
                    </div>
                </div>
                @if(!empty($wizardData['step1']['observaciones']))
                    <p><strong>Observaciones:</strong> {{ $wizardData['step1']['observaciones'] }}</p>
                @endif
                <hr>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <h5><i class="voyager-people"></i> Disponentes ({{ $disponentes->count() }})</h5>
                <div class="row">
                    @foreach($disponentes as $disponente)
                        <div class="col-md-6">
                            <div class="card mb-3" style="border: 1px solid #f1f1f1; padding: 10px; margin-bottom: 10px; border-radius: 5px;">
                                <div class="card-body">
                                    <h6 class="card-title"><strong>{{ $disponente->display_name ?? 'N/A' }}</strong></h6>
                                    <p class="card-text small text-muted mb-1"><strong>Documento:</strong> {{ $disponente->display_document ?? 'N/A' }}</p>
                                    <p class="card-text small text-muted mb-1"><strong>Tipo:</strong> {{ $disponente->person_type ?? 'N/A' }}</p>
                                    <p class="card-text small text-muted mb-1"><strong>Ubicación:</strong>
                                        @php
                                            $ubicacion = 'No especificada';
                                            try {
                                                if ($disponente->municipio) {
                                                    $ubicacion = $disponente->municipio->nombre;
                                                    if ($disponente->municipio->provincia) {
                                                        $ubicacion .= ', ' . $disponente->municipio->provincia->nombre;
                                                    }
                                                }
                                            } catch (\Exception $e) { $ubicacion = 'Error al cargar'; }
                                        @endphp
                                        {{ $ubicacion }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <hr>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <h5><i class="voyager-people"></i> Adquirentes ({{ $adquirentes->count() }})</h5>
                <div class="row">
                    @foreach($adquirentes as $adquirente)
                        <div class="col-md-6">
                            <div class="card mb-3" style="border: 1px solid #f1f1f1; padding: 10px; margin-bottom: 10px; border-radius: 5px;">
                                <div class="card-body">
                                    <h6 class="card-title"><strong>{{ $adquirente->display_name ?? 'N/A' }}</strong></h6>
                                    <p class="card-text small text-muted mb-1"><strong>Documento:</strong> {{ $adquirente->display_document ?? 'N/A' }}</p>
                                    <p class="card-text small text-muted mb-1"><strong>Parentesco:</strong> {{ $adquirente->parentesco_nombre ?? 'No especificado' }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <hr>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <h5><i class="voyager-calculator"></i> Liquidación de Impuesto Determinada</h5>
                <div class="panel panel-bordered" style="border: 1px solid #eee;">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered" style="margin-bottom: 0;">
                            <thead>
                                <tr style="background-color: #f5f5f5;">
                                    <th class="text-center" width="50%">Concepto</th>
                                    <th class="text-center">Detalle / Factor</th>
                                    <th class="text-center">Monto (Bs.)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Impuesto Determinado (IDTGB)</strong></td>
                                    <td class="text-center">Tasa: {{ $liquidacion['tasa_aplicada'] }}%</td>
                                    <td class="text-right">{{ number_format($liquidacion['idtgb_base'], 2) }}</td>
                                </tr>

                                @if($liquidacion['dias_mora'] > 0)
                                    <tr>
                                        <td>Mantenimiento de Valor</td>
                                        <td class="text-center">UFV Final: {{ $liquidacion['ufv_pago'] }}</td>
                                        <td class="text-right">{{ number_format($liquidacion['mantenimiento_valor'], 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Intereses por Mora</td>
                                        <td class="text-center">Tasa: {{ $liquidacion['tasa_mora'] }}% ({{ $liquidacion['dias_mora'] }} días)</td>
                                        <td class="text-right">{{ number_format($liquidacion['interes'], 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Multa por Incumplimiento (IDF)</td>
                                        <td class="text-center">Sanción por mora</td>
                                        <td class="text-right">{{ number_format($liquidacion['multa_idf'], 2) }}</td>
                                    </tr>
                                @else
                                    <tr>
                                        <td colspan="2" class="text-muted text-center"><em>Dentro de plazo legal (Sin recargos)</em></td>
                                        <td class="text-right">0.00</td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot>
                                <tr style="font-size: 1.3em; background-color: #22a7f0; color: white;">
                                    <th colspan="2" class="text-right">TOTAL A PAGAR:</th>
                                    <th class="text-right">Bs. {{ number_format($liquidacion['final'], 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                @if($liquidacion['dias_mora'] > 0)
                    <div class="alert alert-warning" style="margin-top: -10px; border-radius: 0 0 5px 5px;">
                        <i class="voyager-info-circle"></i>
                        Atención: Se han calculado cargos adicionales debido a <strong>{{ $liquidacion['dias_mora'] }} días</strong> de mora desde la fecha de vencimiento ({{ $liquidacion['fecha_vencimiento'] }}).
                    </div>
                @endif
                <hr>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <h5><i class="voyager-folder"></i> Documentos Adjuntos ({{ $documentos->count() }})</h5>
                @if($documentos->count() > 0)
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Nombre Original</th>
                            <th>Asociado a</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($documentos as $doc)
                            <tr>
                                <td>{{ $doc->tipo_doc }}</td>
                                <td>{{ $doc->original_name }}</td>
                                <td>{{ $doc->persona->display_name ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p class="text-muted">No se adjuntaron documentos.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="panel-footer">
        <div class="row">
            <div class="col-md-6">
                <a href="{{ route('admin.tramites.wizard.create.step6') }}" class="btn btn-default">
                    <i class="voyager-angle-left"></i> Anterior
                </a>
                <a href="{{ route('admin.tramites.wizard.cancel') }}" class="btn btn-danger">
                    <i class="voyager-x"></i> Cancelar
                </a>
            </div>
            <div class="col-md-6 text-right">
                <form action="{{ route('admin.tramites.wizard.store') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-success btn-lg" onclick="return confirm('{{ $is_edit ?? false ? "¿Está seguro de que desea actualizar este trámite?" : "¿Está seguro de que desea finalizar y registrar este trámite?" }}')">
                        <i class="voyager-check"></i> {{ $is_edit ?? false ? 'Actualizar Trámite' : 'Confirmar y Guardar Trámite' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
