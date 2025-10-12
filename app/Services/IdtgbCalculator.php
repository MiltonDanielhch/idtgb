<?php

namespace App\Services;

use App\Models\Tramite;
use App\Models\Tasa;
use App\Models\AdquirenteTramite;
use App\Models\TramiteExencion;
use Carbon\Carbon;

class IdtgbCalculator
{
    /**
     * Calcula el IDTGB para un trámite existente, actualiza la BD y devuelve el resumen.
     * Usado internamente por el sistema.
     */
    public function calculateAndSave(Tramite $tramite): array
    {
        // 1. Preparar datos para el cálculo desde el modelo Tramite
        $adquirentesData = $tramite->adquirentes->map(function ($adq) {
            return [
                'parentesco_id' => $adq->parentesco_id,
                'porcentaje' => $adq->porcentaje,
            ];
        })->all();

        $exencionesData = $tramite->exenciones->map(function ($ex) {
            return [
                'tipo' => $ex->tipo,
                'valor' => $ex->valor,
                'monto_maximo' => $ex->monto_maximo,
            ];
        })->all();

        // 2. Realizar el cálculo puro
        $resultados = $this->performCalculation(
            $tramite->base_imponible,
            $tramite->inmueble->municipio->provincia->departamento_id,
            $tramite->tipo_transmision_id,
            $tramite->fecha_presentacion,
            $tramite->fecha_vencimiento,
            $adquirentesData,
            $exencionesData
        );

        // 3. Persistir los resultados en la base de datos
        $tramite->update([
            'total_idtgb'  => $resultados['idtgb'],
            'recargo_mora' => $resultados['recargo'],
            'monto_final'  => $resultados['final'],
        ]);

        // Actualizar proporcional de cada adquirente (asumiendo que el orden no cambió)
        foreach ($tramite->adquirentes as $index => $adq) {
            $adq->update([
                'tasa_aplicada'      => $resultados['detalles_tasas'][$index]['tasa_aplicada'],
                'idtgb_proporcional' => $resultados['detalles_tasas'][$index]['proporcional'],
            ]);
        }

        return $resultados;
    }

    /**
     * Estima el IDTGB a partir de datos crudos, sin persistir nada en la BD.
     * Usado por la calculadora pública.
     */
    public function calculateEstimate(
        float $baseImponible,
        int $departamentoId,
        int $parentescoId,
        int $tipoTransmisionId,
        string $fechaPresentacion,
        string $fechaVencimiento
    ): array {
        // Para la estimación pública, asumimos un único adquirente con el 100%
        $adquirentesData = [
            [
                'parentesco_id' => $parentescoId,
                'porcentaje' => 100,
            ]
        ];

        // La calculadora pública no maneja exenciones
        $exencionesData = [];

        return $this->performCalculation(
            $baseImponible,
            $departamentoId,
            $tipoTransmisionId,
            $fechaPresentacion,
            $fechaVencimiento,
            $adquirentesData,
            $exencionesData
        );
    }

    /**
     * Lógica de cálculo pura, sin efectos secundarios (sin queries de update).
     */
    private function performCalculation(
        float $base,
        int $departamentoId,
        int $tipoTransmisionId,
        string $fechaPresentacion,
        string $fechaVencimiento,
        array $adquirentes,
        array $exenciones
    ): array {
        $totalTasas = 0;
        $detallesTasas = [];

        // 1. Tasas por adquirente
        foreach ($adquirentes as $adq) {
            $tasa = $this->tasaVigente(
                $departamentoId,
                $adq['parentesco_id'],
                $tipoTransmisionId,
                $fechaPresentacion
            );

            $tasaAplicada = $tasa ? $tasa->tasa : 0;
            $porcentaje   = max(0, min(100, $adq['porcentaje']));
            $proporcional = round($base * ($porcentaje / 100) * ($tasaAplicada / 100), 2);

            $totalTasas += $proporcional;
            $detallesTasas[] = [
                'tasa_aplicada' => $tasaAplicada,
                'proporcional' => $proporcional,
            ];
        }

        // 2. Exenciones
        $totalExenciones = 0;
        foreach ($exenciones as $ex) {
            $monto = match ($ex['tipo']) {
                'porcentaje' => min($base * ($ex['valor'] / 100), $ex['monto_maximo'] ?? PHP_FLOAT_MAX),
                default      => min($ex['valor'],               $ex['monto_maximo'] ?? PHP_FLOAT_MAX),
            };
            $totalExenciones += round($monto, 2);
        }

        $idtgb = round(max(0, $totalTasas - $totalExenciones), 2);

        // 3. Recargo por mora
        $diasMora = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($fechaVencimiento), false);
        $recargo  = $diasMora > 0 ? round($idtgb * 0.01 * min($diasMora, 60), 2) : 0;

        $final = round($idtgb + $recargo, 2);

        return [
            'base'           => $base,
            'tasas'          => $totalTasas,
            'exenciones'     => $totalExenciones,
            'idtgb'          => $idtgb,
            'recargo'        => $recargo,
            'final'          => $final,
            'detalles_tasas' => $detallesTasas, // Para uso interno en calculateAndSave
        ];
    }

    /**
     * Busca la tasa vigente para una combinación de parámetros en una fecha dada.
     */
    // private function tasaVigente(int $departamentoId, int $parentescoId, int $tipoTransmisionId, string $fecha): ?Tasa
    // {
    //     return Tasa::where('departamento_id', $departamentoId)
    //                ->where('parentesco_id', $parentescoId)
    //                ->where('tipo_transmision_id', $tipoTransmisionId)
    //                ->where('vigente_desde', '<=', $fecha)
    //                ->where(fn($q) => $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $fecha))
    //                ->first();
    // }
    private function tasaVigente(int $departamentoId, int $parentescoId, ?int $tipoTransmisionId, string $fecha): ?Tasa
    {
        return Tasa::where('departamento_id', $departamentoId)
                ->where('parentesco_id', $parentescoId)
                ->where(function($query) use ($tipoTransmisionId) {
                    // Buscar tasas que coincidan con el tipo de transmisión ESPECÍFICO
                    // O tasas que sean NULL (aplican a todos los tipos)
                    $query->where('tipo_transmision_id', $tipoTransmisionId)
                            ->orWhereNull('tipo_transmision_id');
                })
                ->where('vigente_desde', '<=', $fecha)
                ->where(fn($q) => $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $fecha))
                ->first();
    }
}
