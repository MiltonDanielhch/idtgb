<?php

namespace App\Services;

use App\Models\Tramite;
use App\Models\Tasa;
use App\Models\AdquirenteTramite;
use App\Models\TramiteExencion;

class IdtgbCalculator
{
    public function calcular(Tramite $tramite): array
    {
        $base = $tramite->base_imponible;
        $totalTasas = 0;
        $totalExenciones = 0;

        // 1. Sumar tasas por cada adquirente
        foreach ($tramite->adquirentes as $adq) {
            $tasa = Tasa::where('departamento_id', $tramite->inmueble->municipio->provincia->departamento_id)
                        ->where('parentesco_id', $adq->parentesco_id)
                        ->where('tipo_transmision_id', $tramite->tipo_transmision_id)
                        ->where('vigente_desde', '<=', $tramite->fecha_presentacion)
                        ->where(function ($q) use ($tramite) {
                            $q->whereNull('vigente_hasta')
                              ->orWhere('vigente_hasta', '>=', $tramite->fecha_presentacion);
                        })
                        ->first();

            $tasaAplicada = $tasa ? $tasa->tasa : 0;
            $proporcional = $base * ($adq->porcentaje / 100) * ($tasaAplicada / 100);
            $adq->update(['tasa_aplicada' => $tasaAplicada, 'idtgb_proporcional' => $proporcional]);
            $totalTasas += $proporcional;
        }

        // 2. Restar exenciones
        foreach ($tramite->exenciones as $ex) {
            if ($ex->tipo == 'porcentaje') {
                $monto = min($base * ($ex->valor / 100), $ex->monto_maximo ?? PHP_FLOAT_MAX);
            } else {
                $monto = min($ex->valor, $ex->monto_maximo ?? PHP_FLOAT_MAX);
            }
            $totalExenciones += $monto;
        }

        $idtgb = max(0, $totalTasas - $totalExenciones);

        // Recargo por mora (días después del vencimiento)
        $diasMora = now()->diffInDays($tramite->fecha_vencimiento, false);
        $recargo = 0;
        if ($diasMora > 0) {
            $recargo = $idtgb * 0.01 * min($diasMora, 60); // 1 % diario, tope 60 %
        }

        $final = $idtgb + $recargo;

        // Guardar en el trámite
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
}
