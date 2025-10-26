@extends('voyager::master')

@section('page_title', 'Módulo de Reportes')

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-bar-chart"></i> Módulo de Reportes
    </h1>
@stop

@section('content')
    <div class="page-content browse container-fluid">
        @include('voyager::alerts')
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-body">
                        <h4 class="" style="padding-bottom: 10px"><i class="voyager-settings"></i> Parámetros del Reporte</h4>
                        <form method="GET" action="{{ route('admin.reportes.index') }}" class="form-inline">
                            <div class="form-group">
                                <label for="tipo_reporte">Tipo de Reporte:</label>
                                <select name="tipo_reporte" class="form-control" required>
                                    <option value="recaudacion" {{ (isset($input['tipo_reporte']) && $input['tipo_reporte'] == 'recaudacion') ? 'selected' : '' }}>Recaudación</option>
                                    <option value="tipos_tramite" {{ (isset($input['tipo_reporte']) && $input['tipo_reporte'] == 'tipos_tramite') ? 'selected' : '' }}>Tipos de Trámite</option>
                                    {{-- <option value="rendimiento">Rendimiento de Funcionarios</option> --}}
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="fecha_inicio">Desde:</label>
                                <input type="date" name="fecha_inicio" class="form-control" value="{{ $input['fecha_inicio'] ?? now()->startOfMonth()->format('Y-m-d') }}" required>
                            </div>
                            <div class="form-group">
                                <label for="fecha_fin">Hasta:</label>
                                <input type="date" name="fecha_fin" class="form-control" value="{{ $input['fecha_fin'] ?? now()->endOfMonth()->format('Y-m-d') }}" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Generar</button>
                        </form>
                    </div>
                </div>

                @if($reportData)
                    <div class="panel panel-bordered" style="margin-top: 20px">
                        <div class="panel-heading">
                            <h3 class="panel-title">{{ $reportData['titulo'] }}</h3>
                            <div class="panel-actions">
                                <form method="GET" action="{{ route('admin.reportes.index') }}">
                                    <input type="hidden" name="tipo_reporte" value="{{ $input['tipo_reporte'] }}">
                                    <input type="hidden" name="fecha_inicio" value="{{ $input['fecha_inicio'] }}">
                                    <input type="hidden" name="fecha_fin" value="{{ $input['fecha_fin'] }}">
                                    <input type="hidden" name="exportar" value="pdf">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="voyager-download"></i> Descargar PDF
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="panel-body">
                            <p><strong>Periodo del reporte:</strong> {{ $reportData['fecha_inicio'] }} al {{ $reportData['fecha_fin'] }}</p>
                            
                            @if($reportData['tipo_reporte'] == 'recaudacion')
                                <p><strong>Total Recaudado:</strong> {{ number_format($reportData['total_recaudado'], 2, ',', '.') }} Bs.</p>
                                <hr>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th># Trámite</th>
                                                <th>Fecha de Finalización</th>
                                                <th>Adquirente Principal</th>
                                                <th>Monto Final (Bs.)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($reportData['tramites'] as $tramite)
                                                <tr>
                                                    <td>{{ $tramite->nro_tramite }}</td>
                                                    <td>{{ $tramite->updated_at->format('d/m/Y H:i') }}</td>
                                                    <td>{{ optional(optional($tramite->adquirentes->first())->person)->display_name ?? optional(optional($tramite->adquirentes->first())->person)->full_name ?? 'N/A' }}</td>
                                                    <td style="text-align: right">{{ number_format($tramite->monto_final, 2, ',', '.') }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center">No se encontraron registros en el periodo seleccionado.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="3" style="text-align: right">Total General:</th>
                                                <th style="text-align: right">{{ number_format($reportData['total_recaudado'], 2, ',', '.') }} Bs.</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @elseif($reportData['tipo_reporte'] == 'tipos_tramite')
                                <p><strong>Total General Recaudado:</strong> {{ number_format($reportData['total_general'], 2, ',', '.') }} Bs.</p>
                                <hr>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Tipo de Trámite</th>
                                                <th>Cantidad de Trámites</th>
                                                <th style="text-align: right">Monto Total Recaudado (Bs.)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($reportData['stats'] as $stat)
                                                <tr>
                                                    <td>{{ $stat->tipoTransmision->nombre ?? 'No definido' }}</td>
                                                    <td>{{ $stat->cantidad }}</td>
                                                    <td style="text-align: right">{{ number_format($stat->total, 2, ',', '.') }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center">No se encontraron registros en el periodo seleccionado.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="2" style="text-align: right">Total General:</th>
                                                <th style="text-align: right">{{ number_format($reportData['total_general'], 2, ',', '.') }} Bs.</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@stop