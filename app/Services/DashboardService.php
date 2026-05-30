<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Tramite;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Obtiene todos los datos necesarios para el dashboard.
     */
    public function getData(Request $request): array
    {
        // --- Lógica de Rango de Fechas para KPIs ---
        $range = $request->input('range', 'month');
        $now = Carbon::now();

        switch ($range) {
            case 'today':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $prevStartDate = $now->copy()->subDay()->startOfDay();
                $prevEndDate = $now->copy()->subDay()->endOfDay();
                $kpiLabel = 'Hoy';
                break;
            case 'week':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfWeek();
                $prevStartDate = $now->copy()->subWeek()->startOfWeek();
                $prevEndDate = $now->copy()->subWeek()->endOfWeek();
                $kpiLabel = 'Esta Semana';
                break;
            case 'year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                $prevStartDate = $now->copy()->subYear()->startOfYear();
                $prevEndDate = $now->copy()->subYear()->endOfYear();
                $kpiLabel = 'Este Año';
                break;
            case 'month':
            default:
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                $prevStartDate = $now->copy()->subMonth()->startOfMonth();
                $prevEndDate = $now->copy()->subMonth()->endOfMonth();
                $kpiLabel = 'Este Mes';
                break;
        }

        // --- KPIs dinámicos basados en el rango (cacheados 5 minutos) ---
        $cachePrefix = 'dashboard:' . $range . ':' . $startDate->format('Ymd') . ':' . $endDate->format('Ymd');
        $ttl = now()->addMinutes(5);

        $recaudadoPeriodo = cache()->remember($cachePrefix . ':recaudadoPeriodo', $ttl, function() use ($startDate, $endDate) {
            return Pago::where('estado', 'Aplicado')->whereBetween('fecha_pago', [$startDate, $endDate])->sum('monto');
        });

        $tramitesPeriodo = cache()->remember($cachePrefix . ':tramitesPeriodo', $ttl, function() use ($startDate, $endDate) {
            return Tramite::whereBetween('created_at', [$startDate, $endDate])->count();
        });

        $tramitesFinalizadosPeriodo = cache()->remember($cachePrefix . ':tramitesFinalizadosPeriodo', $ttl, function() use ($startDate, $endDate) {
            return Tramite::where('estado', 'Finalizado')->whereBetween('updated_at', [$startDate, $endDate])->count();
        });

        // --- Datos del período anterior para tendencias ---
        $recaudadoPeriodoAnterior = cache()->remember($cachePrefix . ':recaudadoPeriodoAnterior', $ttl, function() use ($prevStartDate, $prevEndDate) {
            return Pago::where('estado', 'Aplicado')->whereBetween('fecha_pago', [$prevStartDate, $prevEndDate])->sum('monto');
        });

        $tramitesPeriodoAnterior = cache()->remember($cachePrefix . ':tramitesPeriodoAnterior', $ttl, function() use ($prevStartDate, $prevEndDate) {
            return Tramite::whereBetween('created_at', [$prevStartDate, $prevEndDate])->count();
        });

        $tramitesFinalizadosPeriodoAnterior = cache()->remember($cachePrefix . ':tramitesFinalizadosPeriodoAnterior', $ttl, function() use ($prevStartDate, $prevEndDate) {
            return Tramite::where('estado', 'Finalizado')->whereBetween('updated_at', [$prevStartDate, $prevEndDate])->count();
        });

        // Los trámites pendientes son un acumulado, no dependen del rango de fecha.
        $tramitesPendientes = cache()->remember($cachePrefix . ':tramitesPendientes', $ttl, function() {
            return Tramite::whereIn('estado', ['Borrador', 'Observado'])->count();
        });
        $tramitesPendientesAnterior = cache()->remember($cachePrefix . ':tramitesPendientesAnterior', $ttl, function() use ($startDate) {
            return Tramite::whereIn('estado', ['Borrador', 'Observado'])->where('created_at', '<', $startDate)->count();
        });

        // --- Gráficos Dinámicos basados en el rango ---
        $tramitesPorEstado = cache()->remember($cachePrefix . ':tramitesPorEstado', $ttl, function() use ($startDate, $endDate) {
            return Tramite::whereBetween('created_at', [$startDate, $endDate])
                ->select('estado', DB::raw('count(*) as total'))
                ->groupBy('estado')
                ->pluck('total', 'estado')
                ->toArray();
        });

        $tramitesPorTipo = cache()->remember($cachePrefix . ':tramitesPorTipo', $ttl, function() use ($startDate, $endDate) {
            return Tramite::whereBetween('tramites.created_at', [$startDate, $endDate])
                ->join('tipos_transmision', 'tramites.tipo_transmision_id', '=', 'tipos_transmision.id')
                ->select('tipos_transmision.nombre as tipo', DB::raw('count(tramites.id) as total'))
                ->groupBy('tipos_transmision.nombre')
                ->pluck('total', 'tipo')
                ->toArray();
        });

        // La tabla de últimos trámites no depende del rango, siempre muestra los más recientes.
        // Cacheamos un array con solo los datos necesarios para la vista
        $ultimosTramites = cache()->remember($cachePrefix . ':ultimosTramites', $ttl, function() {
            return Tramite::with(['disponentes.person', 'adquirentes.person'])
                ->latest()
                ->take(5)
                ->get()
                ->map(function($tramite) {
                    // Priorizar disponentes para trámites tradicionales, usar adquirentes para simplificados
                    $firstDisponente = $tramite->disponentes->first();
                    $firstAdquirente = $tramite->adquirentes->first();
                    
                    $person = null;
                    if ($firstDisponente) {
                        $person = $firstDisponente->person;
                    } elseif ($firstAdquirente) {
                        $person = $firstAdquirente->person;
                    }
                    
                    return [
                        'id' => $tramite->id,
                        'nro_tramite' => $tramite->nro_tramite,
                        'contribuyente' => $person ? ($person->fullName ?? 'N/A') : 'N/A',
                        'created_at' => $tramite->created_at->format('d M Y'),
                        'monto_final' => $tramite->monto_final,
                        'estado' => $tramite->estado
                    ];
                })
                ->toArray();
        });

        // Gráfico de Recaudación: Agrupar por mes si el rango es el año, si no, por día.
        $isYearRange = ($range === 'year');
        $dateFormat = $isYearRange ? '%Y-%m' : '%Y-%m-%d';
        $periodUnit = $isYearRange ? 'month' : 'day';
        $periodFormat = $isYearRange ? 'Y-m' : 'Y-m-d';

        $recaudacionGrouped = cache()->remember($cachePrefix . ':recaudacionGrouped', $ttl, function() use ($dateFormat, $startDate, $endDate) {
            return Pago::select(DB::raw("SUM(monto) as total"), DB::raw("DATE_FORMAT(fecha_pago, '{$dateFormat}') as period"))
                ->where('estado', 'Aplicado')
                ->whereBetween('fecha_pago', [$startDate, $endDate])
                ->groupBy('period')
                ->pluck('total', 'period')
                ->toArray();
        });

        // fillDateGaps espera una Collection, reconstruir desde el array cacheado
        $recaudacionPeriodoData = $this->fillDateGaps(collect($recaudacionGrouped), $startDate, $endDate, $periodUnit, $periodFormat);

        // El gráfico de comparación anual siempre muestra los últimos 2 años completos.
        $recaudacionAnioActual = cache()->remember($cachePrefix . ':recaudacionAnioActual', $ttl, function() {
            return Pago::select(DB::raw('SUM(monto) as total'), DB::raw('MONTH(fecha_pago) as mes'))
                ->where('estado', 'Aplicado')->whereYear('fecha_pago', now()->year)->groupBy('mes')->orderBy('mes')->pluck('total', 'mes')->all();
        });

        $recaudacionAnioAnterior = cache()->remember($cachePrefix . ':recaudacionAnioAnterior', $ttl, function() {
            return Pago::select(DB::raw('SUM(monto) as total'), DB::raw('MONTH(fecha_pago) as mes'))
                ->where('estado', 'Aplicado')->whereYear('fecha_pago', now()->year - 1)->groupBy('mes')->orderBy('mes')->pluck('total', 'mes')->all();
        });

        $comparacionAnualData = [
            'actual' => array_values(array_replace(array_fill(1, 12, 0), $recaudacionAnioActual)),
            'anterior' => array_values(array_replace(array_fill(1, 12, 0), $recaudacionAnioAnterior)),
        ];

        // --- Preparar datos de tendencia ---
        $trends = [
            'recaudacion' => ['percentage' => $this->calculateTrend($recaudadoPeriodo, $recaudadoPeriodoAnterior)],
            'tramites' => ['percentage' => $this->calculateTrend($tramitesPeriodo, $tramitesPeriodoAnterior)],
            'finalizados' => ['percentage' => $this->calculateTrend($tramitesFinalizadosPeriodo, $tramitesFinalizadosPeriodoAnterior)],
            'pendientes' => ['percentage' => $this->calculateTrend($tramitesPendientes, $tramitesPendientesAnterior)],
        ];

        // Renderizar la tabla de últimos trámites como HTML.
        $ultimosTramitesHtml = view('vendor.voyager.partials.dashboard-tramites-table', ['ultimosTramites' => $ultimosTramites])->render();

        return [
            'kpiLabel' => $kpiLabel,
            'recaudadoPeriodo' => $recaudadoPeriodo,
            'tramitesPeriodo' => $tramitesPeriodo,
            'tramitesFinalizadosPeriodo' => $tramitesFinalizadosPeriodo,
            'tramitesPendientes' => $tramitesPendientes,
            'trends' => $trends,
            // Pasar la colección de últimos trámites también para la renderización inicial en la vista
            'ultimosTramites' => $ultimosTramites,
            'ultimosTramitesHtml' => $ultimosTramitesHtml,
            'recaudacionPeriodoData' => $recaudacionPeriodoData,
            'tramitesPorTipo' => $tramitesPorTipo,
            'tramitesPorEstado' => $tramitesPorEstado,
            'comparacionAnualData' => $comparacionAnualData,
        ];
    }

    /**
     * Obtiene los datos del dashboard y los formatea para una respuesta JSON.
     */
    public function getJsonData(Request $request): array
    {
        $data = $this->getData($request);

        // Formatear los datos para que sean fácilmente consumibles por JavaScript
        return [
            'kpiLabel' => $data['kpiLabel'],
            'recaudadoPeriodoFormatted' => number_format($data['recaudadoPeriodo'], 2, ',', '.') . ' Bs.',
            'tramitesPeriodoFormatted' => number_format($data['tramitesPeriodo'], 0, ',', '.'),
            'tramitesFinalizadosPeriodoFormatted' => number_format($data['tramitesFinalizadosPeriodo'], 0, ',', '.'),
            'tramitesPendientesFormatted' => number_format($data['tramitesPendientes'], 0, ',', '.'),
            'trends' => $data['trends'],
            'ultimosTramitesHtml' => $data['ultimosTramitesHtml'],
            'recaudacionPeriodoData' => [
                'labels' => $data['recaudacionPeriodoData']->keys()->map(fn($item) => Carbon::parse($item)->format('d M'))->values(),
                'values' => $data['recaudacionPeriodoData']->values(),
            ],
            'tramitesPorTipo' => [
                'labels' => $data['tramitesPorTipo']->keys(),
                'values' => $data['tramitesPorTipo']->values(),
            ],
            'tramitesPorEstado' => [
                'labels' => $data['tramitesPorEstado']->keys(),
                'values' => $data['tramitesPorEstado']->values(),
            ],
            // comparacionAnualData no cambia con el filtro, así que no es necesario reenviarlo.
        ];
    }

    /**
     * Calcula el porcentaje de cambio entre dos valores.
     */
    private function calculateTrend($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        $percentage = (($current - $previous) / $previous) * 100;
        return round($percentage, 1);
    }

    /**
     * Rellena los huecos en una colección de datos basada en fechas para asegurar la continuidad en los gráficos.
     *
     * @param \Illuminate\Support\Collection $data
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     * @param string $unit 'day' o 'month'
     * @param string $format Formato de la clave de fecha (e.g., 'Y-m-d' o 'Y-m')
     * @return \Illuminate\Support\Collection
     */
    private function fillDateGaps(\Illuminate\Support\Collection $data, Carbon $start, Carbon $end, string $unit, string $format): \Illuminate\Support\Collection
    {
        $result = collect();
        $currentDate = $start->copy();

        while ($currentDate <= $end) {
            $key = $currentDate->format($format);
            $result->put($key, $data->get($key, 0));

            if ($unit === 'month') {
                $currentDate->addMonthNoOverflow();
            } else {
                $currentDate->addDay();
            }
        }
        return $result;
    }
}
