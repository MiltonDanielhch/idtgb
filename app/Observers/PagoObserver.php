<?php

namespace App\Observers;

use App\Models\Pago;

class PagoObserver
{
    public function created(Pago $pago): void
    {
        $this->clearDashboardCache();
    }

    public function updated(Pago $pago): void
    {
        $this->clearDashboardCache();
    }

    public function deleted(Pago $pago): void
    {
        $this->clearDashboardCache();
    }

    protected function clearDashboardCache(): void
    {
        if (app()->bound(\App\Services\DashboardCacheInvalidator::class)) {
            app(\App\Services\DashboardCacheInvalidator::class)->clearAll();
        }
    }
}
