hice este '''@extends('voyager::master')

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
                        <h3 class="kpi-value">1,250,000 Bs.</h3>
                        <p class="kpi-label">Recaudación del Mes</p>
                        <div class="kpi-trend trend-up">
                            <i class="voyager-up"></i> 8.2%
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-bordered dashboard-kpi">
                    <div class="panel-body text-center">
                        <div class="kpi-icon">
                            <i class="voyager-file-text"></i>
                        </div>
                        <h3 class="kpi-value">152</h3>
                        <p class="kpi-label">Trámites Registrados (Mes)</p>
                        <div class="kpi-trend trend-up">
                            <i class="voyager-up"></i> 5.7%
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-bordered dashboard-kpi">
                    <div class="panel-body text-center">
                        <div class="kpi-icon">
                            <i class="voyager-check"></i>
                        </div>
                        <h3 class="kpi-value">138</h3>
                        <p class="kpi-label">Trámites Finalizados (Mes)</p>
                        <div class="kpi-trend trend-down">
                            <i class="voyager-down"></i> 1.1%
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-bordered dashboard-kpi">
                    <div class="panel-body text-center">
                        <div class="kpi-icon">
                            <i class="voyager-watch"></i>
                        </div>
                        <h3 class="kpi-value">45</h3>
                        <p class="kpi-label">Trámites Pendientes</p>
                        <div class="kpi-trend trend-up">
                            <i class="voyager-up"></i> 2.0%
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Gráfico de recaudación mensual -->
            <div class="col-md-6">
                <div class="panel panel-bordered">
                    <div class="panel-heading">
                        <h3 class="panel-title">Recaudación Mensual (Bs.)</h3>
                    </div>
                    <div class="panel-body">
                        <canvas id="recaudacionMensualChart" height="250"></canvas>
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
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th># Trámite</th>
                                        <th>Contribuyente</th>
                                        <th>Fecha Presentación</th>
                                        <th>Monto Final (Bs.)</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>TR-2023-11-00123</td>
                                        <td>Juan Pérez</td>
                                        <td>20 Oct 2025</td>
                                        <td>12,580.00</td>
                                        <td><span class="label label-success">Finalizado</span></td>
                                        <td>
                                            <a href="#" class="btn btn-sm btn-primary">Ver</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>TR-2023-11-00122</td>
                                        <td>María García</td>
                                        <td>20 Oct 2025</td>
                                        <td>8,950.50</td>
                                        <td><span class="label label-warning">En Proceso</span></td>
                                        <td>
                                            <a href="#" class="btn btn-sm btn-primary">Ver</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>TR-2023-11-00121</td>
                                        <td>Carlos López</td>
                                        <td>19 Oct 2025</td>
                                        <td>21,000.00</td>
                                        <td><span class="label label-info">Iniciado</span></td>
                                        <td>
                                            <a href="#" class="btn btn-sm btn-primary">Ver</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>TR-2023-11-00120</td>
                                        <td>Ana Martínez</td>
                                        <td>19 Oct 2025</td>
                                        <td>5,690.00</td>
                                        <td><span class="label label-danger">Observado</span></td>
                                        <td>
                                            <a href="#" class="btn btn-sm btn-primary">Ver</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>TR-2023-11-00119</td>
                                        <td>Pedro Sánchez</td>
                                        <td>18 Oct 2025</td>
                                        <td>17,830.00</td>
                                        <td><span class="label label-success">Finalizado</span></td>
                                        <td>
                                            <a href="#" class="btn btn-sm btn-primary">Ver</a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js  "></script>

    <script>
        $(document).ready(function(){
            // Configuración de rangos de fecha
            $('.dropdown-menu a').click(function(e) {
                e.preventDefault();
                let range = $(this).data('range');
                $('#refresh-dashboard').html('<i class="voyager-refresh"></i> Actualizando...');

                // Simular carga de datos
                setTimeout(function() {
                    $('#refresh-dashboard').html('<i class="voyager-refresh"></i> Actualizar');
                    toastr.success('Datos actualizados para el período: ' + range);
                }, 1500);
            });

            // Datos de ejemplo para IDTGB
            const recaudacionMensualData = {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                datasets: [{
                    label: 'Recaudación {{ date("Y") }}',
                    data: [850000, 950000, 1100000, 1050000, 1200000, 1300000, 1250000, 1400000, 1350000, 1500000, 1600000, 1800000],
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2
                }]
            };

            const tramitesTipoData = {
                labels: ['Sucesión (Herencia)', 'Donación', 'Legado', 'Otros'],
                datasets: [{
                    label: 'Nro. de Trámites',
                    data: [85, 42, 15, 10],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)'
                    ],
                    borderWidth: 1
                }]
            };

            const tramitesEstadoData = {
                labels: ['Iniciado', 'En Proceso', 'Observado', 'Finalizado', 'Anulado'],
                datasets: [{
                    label: 'Cantidad de Trámites',
                    data: [45, 82, 15, 250, 5],
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2
                }]
            };

            const comparacionAnualData = {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                datasets: [
                    {
                        label: '{{ date("Y") - 1 }}',
                        data: [750000, 850000, 1000000, 950000, 1100000, 1200000, 1150000, 1300000, 1250000, 1400000, 1500000, 1700000],
                        borderColor: 'rgba(201, 203, 207, 1)',
                        backgroundColor: 'rgba(201, 203, 207, 0.2)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: '{{ date("Y") }}',
                        data: [850000, 950000, 1100000, 1050000, 1200000, 1300000, 1250000, 1400000, 1350000, 1500000, 1600000, 1800000],
                        borderColor: 'rgba(54, 162, 235, 1)',
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
            new Chart(document.getElementById('recaudacionMensualChart'), {
                type: 'bar',
                data: recaudacionMensualData,
                options: chartOptions
            });

            new Chart(document.getElementById('tramitesTipoChart'), {
                type: 'pie',
                data: tramitesTipoData,
                options: pieChartOptions
            });

            new Chart(document.getElementById('tramitesEstadoChart'), {
                type: 'bar',
                data: tramitesEstadoData,
                options: barChartOptions
            });

            new Chart(document.getElementById('comparacionAnualChart'), {
                type: 'line',
                data: comparacionAnualData,
                options: chartOptions
            });
        });
    </script>
@stop
''
