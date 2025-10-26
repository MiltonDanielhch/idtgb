<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Pago;
use App\Models\Tramite;

class DashboardCacheInvalidator
{
    /**
     * Lista de sufijos de cache que se usan en el dashboard.
     */
    private array $suffixes = [
        ':recaudadoPeriodo',
        ':tramitesPeriodo',
        ':tramitesFinalizadosPeriodo',
        ':recaudadoPeriodoAnterior',
        ':tramitesPeriodoAnterior',
        ':tramitesFinalizadosPeriodoAnterior',
        ':tramitesPendientes',
        ':tramitesPendientesAnterior',
        ':tramitesPorEstado',
        ':tramitesPorTipo',
        ':ultimosTramites',
        ':recaudacionGrouped',
        ':recaudacionAnioActual',
        ':recaudacionAnioAnterior',
    ];

    /**
     * Borra las claves de cache del dashboard que corresponden a los rangos afectados por un cambio en un Pago.
     */
    public function clearForPago(Pago $pago): void
    {
        // Si el pago tiene fecha_pago, invalidamos los rangos que incluyen esa fecha
        if ($pago->fecha_pago) {
            $this->clearForDate($pago->fecha_pago);
        }

        // También invalidamos el rango actual por si es un pago nuevo
        $this->clearForDate(now());

        // Siempre invalidar las comparaciones anuales si el pago es de este año o el anterior
        if ($pago->fecha_pago?->year === now()->year || $pago->fecha_pago?->year === now()->year - 1) {
            $this->clearAnnualComparisons();
        }
    }

    /**
     * Borra las claves de cache del dashboard que corresponden a los rangos afectados por un cambio en un Trámite.
     */
    public function clearForTramite(Tramite $tramite): void
    {
        // Invalidar rangos que incluyen la fecha de creación
        if ($tramite->created_at) {
            $this->clearForDate($tramite->created_at);
        }

        // Si el trámite fue actualizado, también invalidar rangos de la fecha de actualización
        if ($tramite->updated_at && $tramite->updated_at->ne($tramite->created_at)) {
            $this->clearForDate($tramite->updated_at);
        }

        // Siempre invalidar últimos trámites y estados pendientes
        cache()->forget($this->buildCacheKey('month', now()) . ':ultimosTramites');
        cache()->forget($this->buildCacheKey('month', now()) . ':tramitesPendientes');
        cache()->forget($this->buildCacheKey('month', now()) . ':tramitesPendientesAnterior');
    }

    /**
     * Borra las claves de cache para los rangos que incluyen una fecha específica.
     */
    protected function clearForDate(Carbon $date): void
    {
        // Determinar qué rangos incluyen esta fecha
        $ranges = $this->getAffectedRanges($date);
        
        foreach ($ranges as ['range' => $range, 'start' => $start, 'end' => $end]) {
            $cacheKey = $this->buildCacheKey($range, $start);
            foreach ($this->suffixes as $suffix) {
                cache()->forget($cacheKey . $suffix);
            }
        }
    }

    /**
     * Construye la clave de cache para un rango y fecha específicos.
     */
    protected function buildCacheKey(string $range, Carbon $date): string
    {
        return 'dashboard:' . $range . ':' . $date->format('Ymd') . ':' . $date->copy()->endOf($range === 'today' ? 'day' : $range)->format('Ymd');
    }

    /**
     * Determina qué rangos incluyen una fecha específica.
     * @return array Array de rangos afectados con sus fechas de inicio/fin
     */
    protected function getAffectedRanges(Carbon $date): array
    {
        $ranges = [];
        $now = Carbon::now();

        // Rango diario
        if ($date->isToday()) {
            $ranges[] = [
                'range' => 'today',
                'start' => $date->copy()->startOfDay(),
                'end' => $date->copy()->endOfDay()
            ];
        }

        // Rango semanal
        if ($date->isSameWeek($now)) {
            $ranges[] = [
                'range' => 'week',
                'start' => $date->copy()->startOfWeek(),
                'end' => $date->copy()->endOfWeek()
            ];
        }

        // Rango mensual
        if ($date->isSameMonth($now)) {
            $ranges[] = [
                'range' => 'month',
                'start' => $date->copy()->startOfMonth(),
                'end' => $date->copy()->endOfMonth()
            ];
        }

        // Rango anual
        if ($date->isSameYear($now)) {
            $ranges[] = [
                'range' => 'year',
                'start' => $date->copy()->startOfYear(),
                'end' => $date->copy()->endOfYear()
            ];
        }

        return $ranges;
    }

    /**
     * Borra las claves relacionadas con la comparación anual.
     */
    protected function clearAnnualComparisons(): void
    {
        $now = Carbon::now();
        $cacheKey = $this->buildCacheKey('year', $now);
        cache()->forget($cacheKey . ':recaudacionAnioActual');
        cache()->forget($cacheKey . ':recaudacionAnioAnterior');
    }
}
