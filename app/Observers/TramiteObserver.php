<?php

namespace App\Observers;

use App\Models\Tramite;
use App\Services\IdtgbCalculator;

class TramiteObserver
{
    /**
     * Handle the Tramite "created" event.
     */
    public function created(Tramite $tramite): void
    {
        $this->calcular($tramite);
    }

    /**
     * Handle the Tramite "updated" event.
     */
    public function updated(Tramite $tramite): void
    {
        $this->calcular($tramite);
    }

    /**
     * Handle the Tramite "deleted" event.
     */
    public function deleted(Tramite $tramite): void
    {
        //
    }

    /**
     * Handle the Tramite "restored" event.
     */
    public function restored(Tramite $tramite): void
    {
        //
    }

    /**
     * Handle the Tramite "force deleted" event.
     */
    public function forceDeleted(Tramite $tramite): void
    {
        //
    }
    private function calcular(Tramite $tramite)
    {
        // Solo calcula si hay adquirentes (sinó no hay tasas)
        if ($tramite->adquirentes()->exists()) {
            app(IdtgbCalculator::class)->calcular($tramite);
        }
    }
}
