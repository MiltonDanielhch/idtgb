@extends('voyager::master')

@section('page_header')
    <div class="page-content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h2>Hola, {{ Auth::user()->name }}</h2>
                                <p class="text-muted">Resumen del sistema IDTGB - {{ now()->format('d F Y') }}</p>
                            </div>
                            <div class="col-md-4 text-right">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-primary" id="refresh-dashboard">
                                        <i class="voyager-refresh"></i> Actualizar
                                    </button>
                                    <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">
                                        <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu" role="menu">
                                        <li><a href="#" data-range="today">Hoy</a></li>
                                        <li><a href="#" data-range="week">Esta semana</a></li>
                                        <li><a href="#" data-range="month">Este mes</a></li>
                                        <li><a href="#" data-range="year">Este año</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('content')
    @php
        $meses = array('', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
    @endphp

    {{-- Componente Blade anónimo para mostrar la tendencia --}}
    @php
        $renderTrend = function($percentage) {
            $class = $percentage >= 0 ? 'trend-up' : 'trend-down';
            $icon = $percentage >= 0 ? 'voyager-up' : 'voyager-down';
            $formattedPercentage = number_format(abs($percentage), 1, ',', '.');

            // Asegurarse de que $percentage no sea nulo
            if (is_null($percentage)) {
                return '';
            }

            return <<<HTML
            <div class="kpi-trend {$class}">
                <i class="{$icon}"></i> {$formattedPercentage}%
            </div>
HTML;
        };
    @endphp

    <div class="page-content container-fluid">
        @include('voyager::alerts')
        @include('voyager::dimmers')

        <!-- KPI Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="panel panel-bordered dashboard-kpi">
                    <div class="panel-body text-center">
                        <div class="kpi-icon">
                            <i class="voyager-dollar"></i>
                        </div>
                        <h3 class="kpi-value" id="kpi-recaudacion-valor">{{ number_format($recaudadoPeriodo ?? 0, 2, ',', '.') }} Bs.</h3>
                        <p class="kpi-label" id="kpi-recaudacion-label">Recaudación {{ $kpiLabel ?? 'del Mes' }}</p>
                        {!! $renderTrend($trends['recaudacion']['percentage'] ?? 0) !!}
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-bordered dashboard-kpi">
                    <div class="panel-body text-center">
                        <div class="kpi-icon">
                            <i class="voyager-file-text"></i>
                        </div>
                        <h3 class="kpi-value" id="kpi-tramites-valor">{{ $tramitesPeriodo ?? 0 }}</h3>
                        <p class="kpi-label" id="kpi-tramites-label">Trámites Registrados ({{ $kpiLabel ?? 'Mes' }})</p>
                        {!! $renderTrend($trends['tramites']['percentage'] ?? 0) !!}
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-bordered dashboard-kpi">
                    <div class="panel-body text-center">
                        <div class="kpi-icon">
                            <i class="voyager-check"></i>
                        </div>
                        <h3 class="kpi-value" id="kpi-finalizados-valor">{{ $tramitesFinalizadosPeriodo ?? 0 }}</h3>
                        <p class="kpi-label" id="kpi-finalizados-label">Trámites Finalizados ({{ $kpiLabel ?? 'Mes' }})</p>
                        {!! $renderTrend($trends['finalizados']['percentage'] ?? 0) !!}
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-bordered dashboard-kpi">
                    <div class="panel-body text-center">
                        <div class="kpi-icon"><i class="voyager-watch"></i></div>
                        <h3 class="kpi-value" id="kpi-pendientes-valor">{{ $tramitesPendientes ?? 0 }}</h3>
                        <p class="kpi-label">Trámites Pendientes</p>
                        {!! $renderTrend($trends['pendientes']['percentage'] ?? 0) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Gráfico de recaudación mensual -->
            <div class="col-md-6">
                <div class="panel panel-bordered">
                    <div class="panel-heading">
                        <h3 class="panel-title" id="recaudacion-chart-title">Recaudación por Período (Bs.)</h3>
                    </div>
                    <div class="panel-body">
                        <canvas id="recaudacionPeriodoChart" height="250"></canvas>
                    </div>
                </div>
            </div>

            <!-- Gráfico de trámites por tipo -->
            <div class="col-md-6">
                <div class="panel panel-bordered">
                    <div class="panel-heading">
                        <h3 class="panel-title">Trámites por Tipo</h3>
                    </div>
                    <div class="panel-body">
                        <canvas id="tramitesTipoChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Gráfico de trámites por estado -->
            <div class="col-md-6">
                <div class="panel panel-bordered">
                    <div class="panel-heading">
                        <h3 class="panel-title">Trámites por Estado</h3>
                    </div>
                    <div class="panel-body">
                        <canvas id="tramitesEstadoChart" height="250"></canvas>
                    </div>
                </div>
            </div>

            <!-- Gráfico de comparación anual de recaudación -->
            <div class="col-md-6">
                <div class="panel panel-bordered">
                    <div class="panel-heading">
                        <h3 class="panel-title">Comparación Anual de Recaudación</h3>
                    </div>
                    <div class="panel-body">
                        <canvas id="comparacionAnualChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Tabla de últimos trámites -->
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-heading">
                        <h3 class="panel-title">Últimos Trámites Registrados</h3>
                    </div>
                    <div class="panel-body">
                        @include('vendor.voyager.partials.dashboard-tramites-table', ['ultimosTramites' => $ultimosTramites ?? []])
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .dashboard-kpi {
            transition: all 0.3s ease;
        }
        .dashboard-kpi:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .kpi-icon {
            font-size: 24px;
            color: #22A7F0;
            margin-bottom: 10px;
        }
        .kpi-value {
            font-size: 28px;
            font-weight: bold;
            margin: 10px 0;
        }
        .kpi-label {
            color: #6c757d;
            margin-bottom: 5px;
        }
        .kpi-trend {
            font-size: 12px;
            font-weight: bold;
        }
        .trend-up {
            color: #2ecc71;
        }
        .trend-down {
            color: #e74c3c;
        }
        .panel-heading .btn-group {
            margin-top: -5px;
        }
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }
    </style>
@stop

@section('javascript')
    <!-- Incluir Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Función para renderizar la tendencia, ahora en JS para uso con AJAX
        function renderTrend(percentage) {
            const trendClass = percentage >= 0 ? 'trend-up' : 'trend-down';
            const iconClass = percentage >= 0 ? 'voyager-up' : 'voyager-down';
            const formattedPercentage = Math.abs(percentage).toLocaleString('es-BO', { minimumFractionDigits: 1, maximumFractionDigits: 1 });

            return `
                <div class="kpi-trend ${trendClass}">
                    <i class="${iconClass}"></i> ${formattedPercentage}%
                </div>
            `;
        }

        // Función para actualizar la UI con los nuevos datos
        function updateDashboardUI(data) {
            // Actualizar KPIs
            $('#kpi-recaudacion-valor').text(data.recaudadoPeriodoFormatted);
            $('#kpi-recaudacion-label').text('Recaudación ' + data.kpiLabel);
            $('#kpi-recaudacion-valor').parent().find('.kpi-trend').replaceWith(renderTrend(data.trends.recaudacion.percentage));

            $('#kpi-tramites-valor').text(data.tramitesPeriodoFormatted);
            $('#kpi-tramites-label').text('Trámites Registrados (' + data.kpiLabel + ')');
            $('#kpi-tramites-valor').parent().find('.kpi-trend').replaceWith(renderTrend(data.trends.tramites.percentage));

            $('#kpi-finalizados-valor').text(data.tramitesFinalizadosPeriodoFormatted);
            $('#kpi-finalizados-label').text('Trámites Finalizados (' + data.kpiLabel + ')');
            $('#kpi-finalizados-valor').parent().find('.kpi-trend').replaceWith(renderTrend(data.trends.finalizados.percentage));

            // El KPI de pendientes no cambia con el rango, pero sí su tendencia
            $('#kpi-pendientes-valor').text(data.tramitesPendientesFormatted);
            $('#kpi-pendientes-valor').parent().find('.kpi-trend').replaceWith(renderTrend(data.trends.pendientes.percentage));

            // Actualizar tabla de últimos trámites
            $('#ultimos-tramites-body').parent().parent().replaceWith(data.ultimosTramitesHtml);

            // NOTA: Los gráficos no se actualizan con el filtro de fecha en esta implementación,
            // pero se podrían actualizar de forma similar si se ajusta el controlador.

            // Actualizar Gráficos
            $('#recaudacion-chart-title').text('Recaudación por Período (' + data.kpiLabel + ')');
            recaudacionPeriodoChart.data.labels = data.recaudacionPeriodoData.labels;
            recaudacionPeriodoChart.data.datasets[0].data = data.recaudacionPeriodoData.values;
            recaudacionPeriodoChart.update();

            tramitesTipoChart.data.labels = data.tramitesPorTipo.labels;
            tramitesTipoChart.data.datasets[0].data = data.tramitesPorTipo.values;
            tramitesTipoChart.update();

            tramitesEstadoChart.data.labels = data.tramitesPorEstado.labels;
            tramitesEstadoChart.data.datasets[0].data = data.tramitesPorEstado.values;
            tramitesEstadoChart.update();
        }

        $(document).ready(function(){
            let currentRange = 'month'; // Mantener el rango actual

            function fetchData(range) {
                currentRange = range;
                const refreshButton = $('#refresh-dashboard');
                refreshButton.html('<i class="voyager-refresh"></i> Actualizando...').prop('disabled', true);

                $.ajax({
                    url: '{{ route("admin.dashboard.data") }}',
                    type: 'GET',
                    data: { range: range },
                    success: function(response) {
                        updateDashboardUI(response);
                        toastr.success('Datos actualizados para el período seleccionado.');
                    },
                    error: function() {
                        toastr.error('No se pudieron actualizar los datos. Intente de nuevo.');
                    },
                    complete: function() {
                        refreshButton.html('<i class="voyager-refresh"></i> Actualizar').prop('disabled', false);
                    }
                });
            }

            // Eventos de click para los filtros
            $('.dropdown-menu a[data-range]').click(function(e) {
                e.preventDefault();
                fetchData($(this).data('range'));
            });

            $('#refresh-dashboard').click(() => fetchData(currentRange));

            // --- INICIALIZACIÓN DE GRÁFICOS (sin cambios) ---
            const recaudacionPeriodoData = {
                labels: @json(collect($recaudacionPeriodoData ?? [])->keys()->map(fn($item) => \Carbon\Carbon::parse($item)->format('d M'))),
                datasets: [{
                    label: 'Recaudación',
                    data: @json(collect($recaudacionPeriodoData ?? [])->values()->all()),
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgb(54, 162, 235)',
                    borderWidth: 2
                }]
            };

            const tramitesTipoData = {
                labels: @json(collect($tramitesPorTipo ?? [])->keys()),
                datasets: [{
                    label: 'Nro. de Trámites',
                    data: @json(collect($tramitesPorTipo ?? [])->values()->all()),
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)'
                    ],
                    borderWidth: 1
                }]
            };

            const tramitesEstadoData = {
                labels: @json(collect($tramitesPorEstado ?? [])->keys()),
                datasets: [{
                    label: 'Cantidad de Trámites',
                    data: @json(collect($tramitesPorEstado ?? [])->values()->all()),
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgb(75, 192, 192)',
                    borderWidth: 2
                }]
            };

            const comparacionAnualData = {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                datasets: [
                    {
                        label: '{{ now()->year - 1 }}',
                        data: @json($comparacionAnualData['anterior'] ?? array_fill(0, 12, 0)),
                        borderColor: 'rgb(201, 203, 207)',
                        backgroundColor: 'rgba(201, 203, 207, 0.2)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: '{{ now()->year }}',
                        data: @json($comparacionAnualData['actual'] ?? array_fill(0, 12, 0)),
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }
                ]
            };

            // Configuración común para los gráficos
            const chartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value, index, values) {
                                return new Intl.NumberFormat('es-BO').format(value);
                            }
                        }
                    }
                }
            };

            const pieChartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            };

            const barChartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                 scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            };

            // Crear los gráficos
            const recaudacionPeriodoChart = new Chart(document.getElementById('recaudacionPeriodoChart'), {
                type: 'bar',
                data: recaudacionPeriodoData,
                options: chartOptions
            });

            const tramitesTipoChart = new Chart(document.getElementById('tramitesTipoChart'), {
                type: 'pie',
                data: tramitesTipoData,
                options: pieChartOptions
            });

            const tramitesEstadoChart = new Chart(document.getElementById('tramitesEstadoChart'), {
                type: 'bar',
                data: tramitesEstadoData,
                options: barChartOptions
            });

            const comparacionAnualChart = new Chart(document.getElementById('comparacionAnualChart'), {
                type: 'line',
                data: comparacionAnualData,
                options: chartOptions
            });
        });
    </script>
@stop
