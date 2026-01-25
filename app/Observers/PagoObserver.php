<?php

namespace App\Observers;

use App\Models\Pago;

class PagoObserver
{
    public function created(Pago $pago): void
    {
        $this->clearDashboardCache($pago);
    }

    public function updated(Pago $pago): void
    {
        $this->clearDashboardCache($pago);
    }

    public function deleted(Pago $pago): void
    {
        $this->clearDashboardCache($pago);
    }

    protected function clearDashboardCache(Pago $pago): void
    {
        if (app()->bound(\App\Services\DashboardCacheInvalidator::class)) {
            app(\App\Services\DashboardCacheInvalidator::class)->clearForPago($pago);
        }
    }
}
