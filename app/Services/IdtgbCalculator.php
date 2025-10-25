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
        $inmueble = $tramite->inmuebles->first();
        if (!$inmueble || !$inmueble->municipio) {
            throw new \Exception("El trámite no tiene un inmueble con municipio asociado para el cálculo.");
        }

        // 1. Preparar datos para el cálculo desde el modelo Tramite
        $adquirentesData = $tramite->adquirentes->map(function ($adq) {
            return [
                'parentesco_id' => $adq->parentesco_id,
                'porcentaje' => $adq->porcentaje,
            ];
        })->all();

        // CORREGIDO: Mapear desde la tabla pivote 'tramiteExenciones'
        $exencionesData = $tramite->tramiteExenciones->map(function ($tramiteExencion) {
            $exencion = $tramiteExencion->exencion; // Cargar la relación
            return [
                'tipo' => $exencion->tipo,
                'valor' => $exencion->valor,
                'monto_maximo' => $exencion->monto_maximo,
            ];
        })->all();

        // 2. Realizar el cálculo puro
        $resultados = $this->performCalculation(
            $tramite->base_imponible,
            $inmueble->municipio->provincia->departamento_id,
            $tramite->tipo_transmision_id,
            $tramite->fecha_presentacion,
            $tramite->fecha_transmision->toDateString(), // Pasar fecha_transmision
            $tramite->fecha_vencimiento,
            $adquirentesData,
            $exencionesData
        );

        // 3. Persistir los resultados en la base de datos
        $tramite->update([
            'total_idtgb'  => $resultados['idtgb'],
            'recargo_mora' => $resultados['recargo'],
            'ufv_aplicada' => $resultados['ufv_aplicada'], // ✅ Actualizar UFV aplicada
            'monto_final'  => $resultados['final'],
        ]);

        // Actualizar proporcional de cada adquirente y exención
        foreach ($tramite->adquirentes as $index => $adq) {
            $adq->update([
                'tasa_aplicada'      => $resultados['detalles_tasas'][$index]['tasa_aplicada'],
                'idtgb_proporcional' => $resultados['detalles_tasas'][$index]['proporcional'],
            ]);
        }

        // CORREGIDO: Actualizar el monto real aplicado para cada exención
        foreach ($tramite->tramiteExenciones as $index => $tramiteExencion) {
            // El array 'detalles_exenciones' debe ser creado en performCalculation
            if (isset($resultados['detalles_exenciones'][$index])) {
                $tramiteExencion->update([
                    'monto_aplicado' => $resultados['detalles_exenciones'][$index]['monto_calculado'],
                ]);
            }
        }
        // Recargar la relación para que los nuevos montos estén disponibles
        $tramite->load('tramiteExenciones');
        $resultados['exenciones'] = $tramite->tramiteExenciones->sum('monto_aplicado');

        // Recalcular el IDTGB final con las exenciones actualizadas
        $resultados['idtgb'] = round(max(0, $resultados['tasas'] - $resultados['exenciones']), 2);
        $resultados['final'] = round($resultados['idtgb'] + $resultados['recargo'], 2);

        // Volver a guardar el trámite con el IDTGB y monto final correctos
        $tramite->update([
            'total_idtgb' => $resultados['idtgb'],
            'monto_final' => $resultados['final'],
        ]);

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
        string $fechaTransmision, // ✅ Añadir fechaTransmision
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
            $fechaTransmision, // ✅ Pasar fechaTransmision
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
        string $fechaTransmision, // ✅ Añadir fechaTransmision
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
            $porcentaje   = max(0, min(100, (float) $adq['porcentaje']));
            $proporcional = round($base * ($porcentaje / 100) * ($tasaAplicada / 100), 2);

            $totalTasas += $proporcional;
            $detallesTasas[] = [
                'tasa_aplicada' => $tasaAplicada,
                'proporcional' => $proporcional,
            ];
        }

        // 2. Exenciones
        $totalExenciones = 0;
        $detallesExenciones = []; // <-- NUEVO: Para guardar detalles
        foreach ($exenciones as $ex) {
            $monto = match ($ex['tipo']) {
                'porcentaje' => min($totalTasas * ($ex['valor'] / 100), $ex['monto_maximo'] ?? PHP_FLOAT_MAX),
                default      => min($ex['valor'],               $ex['monto_maximo'] ?? PHP_FLOAT_MAX),
            };
            $montoCalculado = round($monto, 2);
            $totalExenciones += $montoCalculado;
            $detallesExenciones[] = [
                'monto_calculado' => $montoCalculado
            ];
        }

        $idtgb = round(max(0, $totalTasas - $totalExenciones), 2);

        // 3. Recargo por mora
        $recargo = 0;
        $ahora = \Carbon\Carbon::parse($fechaPresentacion)->startOfDay();
        $vencimiento = \Carbon\Carbon::parse($fechaVencimiento)->startOfDay();

        $diasMora = 0;
        if ($ahora->isAfter($vencimiento)) {
            $diasMora = $ahora->diffInDays($vencimiento);
            $recargo = round($idtgb * 0.01 * min($diasMora, 60), 2);
        }

        $final = round($idtgb + $recargo, 2);

        // 4. Obtener UFV aplicada (la más reciente en o antes de la fecha de transmisión)
        $ufvAplicada = \App\Models\Ufv::whereDate('fecha', '<=', $fechaTransmision)
                                       ->orderBy('fecha', 'desc')->first()?->valor ?? 1.00000;

        return [
            'base'           => $base,
            'tasas'          => $totalTasas,
            'exenciones'     => $totalExenciones,
            'idtgb'          => $idtgb,
            'recargo'        => $recargo,
            'final'          => $final,
            'dias_mora'      => $diasMora,
            'ufv_aplicada'   => $ufvAplicada, // ✅ Devolver UFV aplicada
            'detalles_tasas' => $detallesTasas,
            'detalles_exenciones' => $detallesExenciones, // <-- NUEVO: Devolver detalles
        ];
    }

    /**
     * Busca la tasa vigente para una combinación de parámetros en una fecha dada.
     */
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
                ->orderBy('tipo_transmision_id', 'desc') // Priorizar la tasa específica sobre la genérica
                ->first();
    }
}
