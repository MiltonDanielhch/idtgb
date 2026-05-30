<?php

namespace App\Services;

use App\Models\Feriado;
use Carbon\Carbon;

class DiasHabilesService
{
    /**
     * Calcular fecha de vencimiento contando días hábiles
     * Excluye sábados, domingos y feriados (nacionales y departamentales)
     */
    public function calcularVencimiento($fechaInicio, $diasHabiles, $departamentoId = null): Carbon
    {
        $fecha = Carbon::parse($fechaInicio);
        $diasContados = 0;
        $maxIteraciones = 365; // Límite de seguridad para evitar bucles infinitos
        $iteraciones = 0;

        while ($diasContados < $diasHabiles && $iteraciones < $maxIteraciones) {
            $fecha->addDay();
            $iteraciones++;

            if (!$this->esDiaHabil($fecha, $departamentoId)) {
                continue;
            }

            $diasContados++;
        }

        if ($iteraciones >= $maxIteraciones) {
            throw new \Exception("No se pudo calcular la fecha de vencimiento: excedido límite de iteraciones");
        }

        return $fecha;
    }

    /**
     * Verificar si una fecha es día hábil
     * Retorna true si es día hábil (no es fin de semana ni feriado)
     */
    public function esDiaHabil($fecha, $departamentoId = null): bool
    {
        $carbonFecha = is_string($fecha) ? Carbon::parse($fecha) : $fecha;

        // Excluir fines de semana
        if ($carbonFecha->isWeekend()) {
            return false;
        }

        // Excluir feriados
        if ($this->esFeriado($carbonFecha, $departamentoId)) {
            return false;
        }

        return true;
    }

    /**
     * Verificar si una fecha es feriado
     */
    public function esFeriado($fecha, $departamentoId = null): bool
    {
        $fechaStr = is_string($fecha) ? $fecha : $fecha->toDateString();
        
        return Feriado::where('fecha', $fechaStr)
            ->where('activo', true)
            ->where(function ($q) use ($departamentoId) {
                // Feriados nacionales (sin departamento) o del departamento específico
                $q->whereNull('departamento_id')
                  ->orWhere('departamento_id', $departamentoId);
            })
            ->exists();
    }

    /**
     * Contar días hábiles entre dos fechas
     */
    public function contarDiasHabiles($fechaInicio, $fechaFin, $departamentoId = null): int
    {
        $inicio = Carbon::parse($fechaInicio);
        $fin = Carbon::parse($fechaFin);
        $diasHabiles = 0;

        $fechaActual = $inicio->copy();
        while ($fechaActual->lte($fin)) {
            if ($this->esDiaHabil($fechaActual, $departamentoId)) {
                $diasHabiles++;
            }
            $fechaActual->addDay();
        }

        return $diasHabiles;
    }

    /**
     * Obtener lista de días no hábiles en un rango de fechas
     * Retorna array con información de por qué cada día no es hábil
     */
    public function obtenerDiasNoHabiles($fechaInicio, $fechaFin, $departamentoId = null): array
    {
        $inicio = Carbon::parse($fechaInicio);
        $fin = Carbon::parse($fechaFin);
        $diasNoHabiles = [];

        $fechaActual = $inicio->copy();
        while ($fechaActual->lte($fin)) {
            if (!$this->esDiaHabil($fechaActual, $departamentoId)) {
                $razon = $this->obtenerRazonNoHabil($fechaActual, $departamentoId);
                $diasNoHabiles[] = [
                    'fecha' => $fechaActual->toDateString(),
                    'razon' => $razon,
                ];
            }
            $fechaActual->addDay();
        }

        return $diasNoHabiles;
    }

    /**
     * Obtener la razón por la que un día no es hábil
     */
    private function obtenerRazonNoHabil($fecha, $departamentoId = null): string
    {
        if ($fecha->isWeekend()) {
            return 'Fin de semana';
        }

        if ($this->esFeriado($fecha, $departamentoId)) {
            $feriado = Feriado::where('fecha', $fecha->toDateString())
                ->where('activo', true)
                ->where(function ($q) use ($departamentoId) {
                    $q->whereNull('departamento_id')
                      ->orWhere('departamento_id', $departamentoId);
                })
                ->first();

            return 'Feriado: ' . ($feriado ? $feriado->nombre : 'Desconocido');
        }

        return 'Desconocido';
    }
}
