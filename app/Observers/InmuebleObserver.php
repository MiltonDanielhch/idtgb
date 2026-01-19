<?php

namespace App\Observers;

use App\Models\Inmueble;

class InmuebleObserver
{
    /**
     * Handle Inmueble "created" event.
     * NOTA: No actualizamos nada aquí porque created_by se asigna manualmente
     */
    public function created(Inmueble $inmueble): void
    {
        // El campo created_by se asigna manualmente en el controlador
    }

    /**
     * Handle Inmueble "updated" event.
     */
    public function updated(Inmueble $inmueble): void
    {
        if (auth()->check()) {
            $inmueble->update(['updated_by' => auth()->id()]);
        }
    }

    /**
     * Handle Inmueble "deleted" event.
     */
    public function deleted(Inmueble $inmueble): void
    {
        if (auth()->check()) {
            $inmueble->update(['updated_by' => auth()->id()]);
        }
    }

    /**
     * Handle Inmueble "restored" event.
     */
    public function restored(Inmueble $inmueble): void
    {
        if (auth()->check()) {
            $inmueble->update(['updated_by' => auth()->id()]);
        }
    }

    /**
     * Handle Inmueble "force deleted" event.
     */
    public function forceDeleted(Inmueble $inmueble): void
    {
        // Lógica para cuando se elimina permanentemente (forzado)
    }
}
