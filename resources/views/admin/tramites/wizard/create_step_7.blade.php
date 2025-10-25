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

        <!-- Resumen de Datos Generales -->
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

        <!-- Resumen de Disponentes -->
        <div class="row">
            <div class="col-md-12">
                <h5><i class="voyager-people"></i> Disponentes ({{ $disponentes->count() }})</h5>
                <div class="row">
                    @foreach($disponentes as $disponente)
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">{{ $disponente->display_name ?? 'N/A' }}</h6>
                                    <p class="card-text small text-muted mb-1">
                                        <strong>Documento:</strong> {{ $disponente->display_document ?? 'N/A' }}
                                    </p>
                                    <p class="card-text small text-muted mb-1">
                                        <strong>Tipo:</strong> {{ $disponente->person_type ?? 'N/A' }}
                                    </p>
                                    <p class="card-text small text-muted mb-1">
                                        <strong>Ubicación:</strong>
                                        @php
                                            $ubicacion = 'Ubicación no especificada';
                                            try {
                                                if ($disponente->municipio) {
                                                    $ubicacion = $disponente->municipio->nombre ?? 'Municipio no especificado';
                                                    if ($disponente->municipio->provincia) {
                                                        $ubicacion .= ', ' . ($disponente->municipio->provincia->nombre ?? 'Provincia no especificada');
                                                        if ($disponente->municipio->provincia->departamento) {
                                                            $ubicacion .= ', ' . ($disponente->municipio->provincia->departamento->nombre ?? 'Departamento no especificado');
                                                        }
                                                    }
                                                }
                                            } catch (\Exception $e) {
                                                $ubicacion = 'Ubicación no disponible';
                                            }
                                        @endphp
                                        {{ $ubicacion }}
                                    </p>
                                    @if($disponente->email)
                                        <p class="card-text small text-muted mb-1">
                                            <strong>Email:</strong> {{ $disponente->email }}
                                        </p>
                                    @endif
                                    @if($disponente->phone)
                                        <p class="card-text small text-muted mb-1">
                                            <strong>Teléfono:</strong> {{ $disponente->phone }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <hr>
            </div>
        </div>

        <!-- Resumen de Adquirentes -->
        <div class="row">
            <div class="col-md-12">
                <h5><i class="voyager-people"></i> Adquirentes ({{ $adquirentes->count() }})</h5>
                <div class="row">
                    @foreach($adquirentes as $adquirente)
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">{{ $adquirente->display_name ?? 'N/A' }}</h6>
                                    <p class="card-text small text-muted mb-1">
                                        <strong>Documento:</strong> {{ $adquirente->display_document ?? 'N/A' }}
                                    </p>
                                    <p class="card-text small text-muted mb-1">
                                        <strong>Tipo:</strong> {{ $adquirente->person_type ?? 'N/A' }}
                                    </p>
                                    <p class="card-text small text-muted mb-1">
                                        <strong>Parentesco:</strong> {{ $adquirente->parentesco_nombre ?? 'No especificado' }}
                                    </p>
                                    <p class="card-text small text-muted mb-1">
                                        <strong>Ubicación:</strong>
                                        @php
                                            $ubicacion = 'Ubicación no especificada';
                                            try {
                                                if ($adquirente->municipio) {
                                                    $ubicacion = $adquirente->municipio->nombre ?? 'Municipio no especificado';
                                                    if ($adquirente->municipio->provincia) {
                                                        $ubicacion .= ', ' . ($adquirente->municipio->provincia->nombre ?? 'Provincia no especificada');
                                                        if ($adquirente->municipio->provincia->departamento) {
                                                            $ubicacion .= ', ' . ($adquirente->municipio->provincia->departamento->nombre ?? 'Departamento no especificado');
                                                        }
                                                    }
                                                }
                                            } catch (\Exception $e) {
                                                $ubicacion = 'Ubicación no disponible';
                                            }
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

        <!-- Resumen de Inmuebles -->
        <div class="row">
            <div class="col-md-12">
                <h5><i class="voyager-home"></i> Inmuebles ({{ $inmuebles->count() }})</h5>
                <div class="row">
                    @foreach($inmuebles as $inmueble)
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Catastro: {{ $inmueble->catastro ?? 'N/A' }}</h6>
                                    <p class="card-text small text-muted mb-1">
                                        <strong>Dirección:</strong> {{ $inmueble->direccion ?? 'N/A' }}
                                    </p>
                                    <p class="card-text small text-muted mb-1">
                                        <strong>Tipo Inmueble:</strong> {{ $inmueble->tipoInmueble->nombre ?? 'N/A' }}
                                    </p>
                                    <p class="card-text small text-muted mb-1">
                                        <strong>Ubicación:</strong>
                                        @php
                                            $ubicacion = 'Ubicación no especificada';
                                            try {
                                                if ($inmueble->municipio) {
                                                    $ubicacion = $inmueble->municipio->nombre ?? 'Municipio no especificado';
                                                    if ($inmueble->municipio->provincia) {
                                                        $ubicacion .= ', ' . ($inmueble->municipio->provincia->nombre ?? 'Provincia no especificada');
                                                        if ($inmueble->municipio->provincia->departamento) {
                                                            $ubicacion .= ', ' . ($inmueble->municipio->provincia->departamento->nombre ?? 'Departamento no especificado');
                                                        }
                                                    }
                                                }
                                            } catch (\Exception $e) {
                                                $ubicacion = 'Ubicación no disponible';
                                            }
                                        @endphp
                                        {{ $ubicacion }}
                                    </p>
                                    <p class="card-text small text-muted mb-1">
                                        <strong>Valor Catastral:</strong>
                                        {{ isset($inmueble->valor_catastral) ? 'Bs. ' . number_format($inmueble->valor_catastral, 2) : 'N/A' }}
                                    </p>
                                    @if($inmueble->matricula_rr)
                                        <p class="card-text small text-muted mb-1">
                                            <strong>Matrícula RR:</strong> {{ $inmueble->matricula_rr }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <hr>
            </div>
        </div>

        <!-- Resumen de Documentos -->
        <div class="row">
            <div class="col-md-12">
                <h5><i class="voyager-folder"></i> Documentos a Guardar ({{ $documentos->count() }})</h5>
                @if($documentos->count() > 0)
                <table class="table table-striped table-condensed">
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
                <hr>
            </div>
        </div>

        <!-- Resumen de Exenciones -->
        <div class="row">
            <div class="col-md-12">
                <h5><i class="voyager-gift"></i> Exenciones Aplicadas ({{ $exenciones->count() }})</h5>
                @if($exenciones->count() > 0)
                <table class="table table-striped table-condensed">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($exenciones as $exencion)
                            <tr>
                                <td>{{ $exencion->nombre }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $exencion->tipo)) }}</td>
                                <td>{{ $exencion->tipo == 'porcentaje' ? number_format($exencion->valor, 2) . '%' : 'Bs. ' . number_format($exencion->valor, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p class="text-muted">No se aplicaron exenciones.</p>
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
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="voyager-check"></i> Confirmar y Guardar Trámite
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
