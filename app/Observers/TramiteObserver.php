<?php

namespace App\Observers;

use App\Models\Tramite;
use App\Services\IdtgbCalculator;
use App\Services\DashboardCacheInvalidator;
use App\Jobs\ExportarAlSINJob;

class TramiteObserver
{
    /**
     * Se ejecuta antes de guardar (crear o actualizar).
     * Ideal para validaciones de integridad y seguridad.
     */
    public function saving(Tramite $tramite): void
    {
        // SEGURIDAD: Si el trámite tiene un hash de validación (ya fue revisado)
        // y se intenta cambiar un dato sensible, invalidamos el hash.
        if ($tramite->exists && !empty($tramite->hash_validacion)) {
            $camposCriticos = [
                'base_imponible',
                'fecha_transmision',
                'tipo_transmision_id',
                'tipo_contribuyente'
            ];

            if ($tramite->isDirty($camposCriticos)) {
                $tramite->hash_validacion = null;
            }
        }
    }

    /**
     * Se ejecuta después de que el trámite ha sido actualizado en la BD.
     */
    public function updated(Tramite $tramite): void
    {
        // 1. Recalcular montos si el estado es Borrador (para mantener la boleta al día)
        $this->ejecutarRecalculo($tramite);

        // 2. Exportación al SIN si el trámite se finaliza o paga con éxito
        if ($tramite->isDirty('estado')) {
            if (in_array($tramite->estado, ['Finalizado', 'Pagado'])) {
                dispatch(new ExportarAlSINJob($tramite));
            }
        }

        // 3. Invalida el caché del Dashboard para que los contadores se actualicen
        $this->limpiarCache();
    }

    public function created(Tramite $tramite): void
    {
        $this->limpiarCache();
    }

    public function deleted(Tramite $tramite): void
    {
        $this->limpiarCache();
    }

    /**
     * Lógica interna para decidir cuándo llamar al Calculador.
     */
    private function ejecutarRecalculo(Tramite $tramite): void
    {
        // Solo recalculamos automáticamente si el trámite está en flujo activo
        // y tiene adquirentes cargados. No tocamos trámites "Finalizados" o "Anulados".
        if (in_array($tramite->estado, ['Borrador', 'Pendiente', 'Pagado'])) {
            if ($tramite->adquirentes()->exists()) {
                // Evitamos un bucle infinito: el calculador hace un ->update(),
                // por lo que usamos 'withoutEvents' dentro del servicio o controlamos aquí.
                app(IdtgbCalculator::class)->calculateAndSave($tramite);
            }
        }
    }

    /**
     * Limpia la caché del sistema para refrescar reportes.
     */
    private function limpiarCache(): void
    {
        if (app()->bound(DashboardCacheInvalidator::class)) {
            app(DashboardCacheInvalidator::class)->clearAll();
        }
    }
}
