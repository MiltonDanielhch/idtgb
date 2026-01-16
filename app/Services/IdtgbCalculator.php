<?php

namespace App\Services;

use App\Models\Tramite;
use App\Models\Tasa;
use App\Models\Ufv;
use Carbon\Carbon;

class IdtgbCalculator
{
    /**
     * Lógica para trámites oficiales grabados en base de datos.
     */
    /**
     * Lógica para trámites oficiales grabados en base de datos.
     */
    public function calculateAndSave(Tramite $tramite): array
    {
        // 1. Asegurar que las relaciones están cargadas
        $tramite->load(['inmuebles.municipio.provincia.departamento', 'adquirentes']);

        $porcentajeParticipacion = $tramite->adquirentes->sum('porcentaje') ?: 100;

        $adquirentesData = $tramite->adquirentes->map(function ($adq) {
            return [
                'parentesco_id' => $adq->parentesco_id,
                'porcentaje' => $adq->porcentaje,
            ];
        })->all();

        // 2. Ejecutar el cálculo CORE
        $resultados = $this->performCalculation(
            $tramite->base_imponible,
            $tramite->inmuebles->first()->municipio->provincia->departamento_id ?? 1, // Default Beni
            $tramite->tipo_transmision_id,
            $tramite->fecha_presentacion->toDateString(), // Usar fecha de registro del trámite
            $tramite->fecha_transmision->toDateString(),
            $tramite->fecha_vencimiento->toDateString(),
            $adquirentesData,
            [],
            $tramite->tipo_contribuyente ?? 'Natural',
            $porcentajeParticipacion
        );

        // MAPEO CORRECTO SEGÚN TU SCHEMA:
        $tramite->update([
            'total_idtgb'  => $resultados['idtgb_base'], // Coincide con tu Schema
            'recargo_mora' => $resultados['mantenimiento_valor'] + $resultados['interes'] + $resultados['multa_idf'], // Coincide
            'monto_final'  => $resultados['final'], // Coincide
            'ufv_aplicada' => $resultados['ufv_pago'], // Coincide
        ]);

        // 4. ACTUALIZACIÓN DE LOS ADQUIRENTES (Para que el Formulario A-01 no salga en 0)
        foreach ($tramite->adquirentes as $adq) {
            // Buscamos la tasa específica para este adquirente
            $tasaModel = $this->tasaVigente(
                $tramite->inmuebles->first()->municipio->provincia->departamento_id,
                $adq->parentesco_id,
                $tramite->fecha_presentacion
            );

            $tasaVal = $tasaModel ? $tasaModel->tasa : 0;
            $baseSujeto = $tramite->base_imponible * ($adq->porcentaje / 100);

            // Actualizamos la tabla pivot o modelo Adquirente
            $adq->update([
                'tasa_aplicada' => $tasaVal,
                'idtgb_proporcional' => round($baseSujeto * ($tasaVal / 100), 2)
            ]);
        }

        return $resultados;
    }

    /**
     * Lógica para la Calculadora Pública (Estilo Cochabamba/Santa Cruz).
     */
    public function calculateEstimate($base, $depId, $parId, $tipoId, $fTrans, $fPres, $fVenc, $contribuyente = 'Natural', $participacion = 100): array
    {
        return $this->performCalculation(
            $base, $depId, $tipoId, $fPres, $fTrans, $fVenc,
            [['parentesco_id' => $parId, 'porcentaje' => 100]],
            [], $contribuyente, $participacion
        );
    }

    /**
     * CORE: Cálculo bajo Ley 812 con factor de participación.
     */
    private function performCalculation($base, $depId, $tipoId, $fPres, $fTrans, $fVenc, $adquirentes, $exenciones, $tipoContribuyente, $participacion): array
    {
        // 1. Aplicar Factor de Participación (Estilo Cochabamba)
        $baseImponibleParticipacion = $base * ($participacion / 100);

        // 2. Cálculo del Tributo Omitido (TO)
        $totalTasas = 0;
        $tasaAplicadaDecimal = 0;

        foreach ($adquirentes as $adq) {
            $tasaModel = $this->tasaVigente($depId, $adq['parentesco_id'], $fPres);
            $tasaVal = $tasaModel ? $tasaModel->tasa : 0;
            $tasaAplicadaDecimal = $tasaVal; // Para mostrar en el reporte

            $proporcional = round($baseImponibleParticipacion * ($tasaVal / 100), 2);
            $totalTasas += $proporcional;
        }

        $idtgbBase = max(0, $totalTasas);

        // 3. Variables de Mora y Actualización (Estilo Santa Cruz)
        $mantenimientoValor = 0;
        $interes = 0;
        $multaIdf = 0;
        $diasMora = 0;
        $r_interes_display = 0;

        $fechaPago = Carbon::parse($fPres)->startOfDay();
        $fechaVenc = Carbon::parse($fVenc)->startOfDay();

        $ufvVencimiento = Ufv::getValorEnFecha($fechaVenc);
        $ufvPago = Ufv::getValorEnFecha($fechaPago);

        if ($fechaPago->isAfter($fechaVenc)) {
            $diasMora = $fechaPago->diffInDays($fechaVenc);

            // A. Mantenimiento de Valor
            $tributoActualizado = $idtgbBase * ($ufvPago / $ufvVencimiento);
            $mantenimientoValor = max(0, $tributoActualizado - $idtgbBase);

            // B. Intereses (Ley 812 - Escalonado)
            $aniosMora = $diasMora / 360;
            $r = 0.04;
            if ($aniosMora > 4) $r = 0.06;
            if ($aniosMora > 7) $r = 0.10;

            $r_interes_display = $r * 100;
            $interes = $tributoActualizado * (pow(1 + ($r / 360), $diasMora) - 1);

            // C. Multa IDF (50 o 100 UFVs)
            $cantUfvMulta = ($tipoContribuyente === 'Jurídica') ? 100 : 50;
            $multaIdf = $cantUfvMulta * $ufvPago;
        }

        $recargoTotal = $mantenimientoValor + $interes + $multaIdf;
        $final = $idtgbBase + $recargoTotal;

        return [
            'nro_tramite' => 'REF-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT),
            'base_original' => $base,
            'participacion' => $participacion,
            'base_imponible_calculada' => $baseImponibleParticipacion,
            'tasa_aplicada' => $tasaAplicadaDecimal,
            'idtgb_base' => round($idtgbBase, 2),        // S900
            'mantenimiento_valor' => round($mantenimientoValor, 2), // S920
            'interes' => round($interes, 2),            // S930
            'tasa_mora' => $r_interes_display,          // 4%, 6% o 10%
            'multa_idf' => round($multaIdf, 2),         // S900 (Multa)
            'final' => round($final, 2),                // Total Deuda
            'dias_mora' => $diasMora,
            'ufv_vencimiento' => $ufvVencimiento,
            'ufv_pago' => $ufvPago,
            'fecha_transmision' => Carbon::parse($fTrans)->format('d/m/Y'),
            'fecha_vencimiento' => $fechaVenc->format('d/m/Y'),
            'fecha_pago' => $fechaPago->format('d/m/Y'),
        ];
    }

    private function tasaVigente($depId, $parId, $fecha)
    {
        return Tasa::where('departamento_id', $depId)
            ->where('parentesco_id', $parId)
            ->where('vigente_desde', '<=', $fecha)
            ->where(fn($q) => $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $fecha))
            ->first();
    }
}
