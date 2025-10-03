<?php

namespace App\Services;

use App\Models\Tramite;
use App\Models\Tasa;
use App\Models\AdquirenteTramite;
use App\Models\TramiteExencion;

class IdtgbCalculator
{
    /**
     * Calcula el IDTGB de un trámite y actualiza los montos.
     * Devuelve array resumen.
     */
    public function calcular(Tramite $tramite): array
    {
        $base              = $tramite->base_imponible;
        $totalTasas        = 0;
        $totalExenciones   = 0;

        // 1. Tasas por adquirente
        foreach ($tramite->adquirentes as $adq) {
            $tasa = $this->tasaVigente(
                $tramite->inmueble->municipio->provincia->departamento_id,
                $adq->parentesco_id,
                $tramite->tipo_transmision_id,
                $tramite->fecha_presentacion->toDateString()
            );

            $tasaAplicada = $tasa ? $tasa->tasa : 0;
            $porcentaje   = max(0, min(100, $adq->porcentaje));
            $proporcional = round($base * ($porcentaje / 100) * ($tasaAplicada / 100), 2);

            $adq->update([
                'tasa_aplicada'      => $tasaAplicada,
                'idtgb_proporcional' => $proporcional,
            ]);

            $totalTasas += $proporcional;
        }

        // 2. Exenciones
        foreach ($tramite->exenciones as $ex) {
            $monto = match ($ex->tipo) {
                'porcentaje' => min($base * ($ex->valor / 100), $ex->monto_maximo ?? PHP_FLOAT_MAX),
                default      => min($ex->valor,               $ex->monto_maximo ?? PHP_FLOAT_MAX),
            };
            $totalExenciones += round($monto, 2);
        }

        $idtgb   = round(max(0, $totalTasas - $totalExenciones), 2);

        // 3. Recargo por mora
        $diasMora = now()->diffInDays($tramite->fecha_vencimiento, false);
        $recargo  = $diasMora > 0 ? round($idtgb * 0.01 * min($diasMora, 60), 2) : 0;

        $final = round($idtgb + $recargo, 2);

        // 4. Guardar en trámite
        $tramite->update([
            'total_idtgb'  => $idtgb,
            'recargo_mora' => $recargo,
            'monto_final'  => $final,
        ]);

        return [
            'base'        => $base,
            'tasas'       => $totalTasas,
            'exenciones'  => $totalExenciones,
            'idtgb'       => $idtgb,
            'recargo'     => $recargo,
            'final'       => $final,
        ];
    }

    /* ------------------------------------------------------------------
     *  PRIVATE
     * ------------------------------------------------------------------ */

    private function tasaVigente(int $departamentoId, int $parentescoId, int $tipoTransmisionId, string $fecha): ?Tasa
    {
        return Tasa::where('departamento_id', $departamentoId)
                   ->where('parentesco_id', $parentescoId)
                   ->where('tipo_transmision_id', $tipoTransmisionId)
                   ->where('vigente_desde', '<=', $fecha)
                   ->where(fn($q) => $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $fecha))
                   ->first();
    }
}
