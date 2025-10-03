<?php

namespace App\Observers;

use App\Models\Tramite;
use App\Services\IdtgbCalculator;

class TramiteObserver
{
    public function created(Tramite $tramite): void
    {
        $this->calcular($tramite);
    }

    public function updated(Tramite $tramite): void
    {
        $this->calcular($tramite);
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

        app(IdtgbCalculator::class)->calcular($tramite);
    }
}
