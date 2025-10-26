<?php

namespace App\Observers;

use App\Jobs\ExportarAlSINJob;
use App\Models\Tramite;
use App\Services\IdtgbCalculator;
use App\Services\DashboardCacheInvalidator;

class TramiteObserver
{
    // public function created(Tramite $tramite): void
    // {
    //     $this->calcular($tramite);
    // }

    public function updated(Tramite $tramite): void
    {
        $this->calcular($tramite);

        if ($tramite->isDirty('estado') && $tramite->estado === 'Finalizado') {
            dispatch(new ExportarAlSINJob($tramite));
        }

        // Invalidar cache del dashboard cuando un trámite cambia
        if (app()->bound(DashboardCacheInvalidator::class)) {
            app(DashboardCacheInvalidator::class)->clearAll();
        }
    }

    public function created(Tramite $tramite): void
    {
        // Al crear un trámite también invalidamos cache (p. ej. ultimosTramites)
        if (app()->bound(DashboardCacheInvalidator::class)) {
            app(DashboardCacheInvalidator::class)->clearAll();
        }
    }

    public function deleted(Tramite $tramite): void
    {
        if (app()->bound(DashboardCacheInvalidator::class)) {
            app(DashboardCacheInvalidator::class)->clearAll();
        }
    }

    private function calcular(Tramite $tramite): void
    {
        // Solo calcula si hay adquirentes y el trámite está en Borrador o Pagado
        if (! $tramite->adquirentes()->exists()) {
            return;
        }

        if (! in_array($tramite->estado, ['Borrador', 'Pagado'])) {
            return;
        }

        app(IdtgbCalculator::class)->calculateAndSave($tramite);
    }
}
