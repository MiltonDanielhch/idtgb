<?php

namespace App\Services;

use App\Models\Tramite;
use App\Models\Tasa;
use App\Models\Ufv;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class IdtgbCalculator
{
    /**
     * Alias para compatibilidad con controladores existentes.
     */
    public function calcular(Tramite $tramite): array
    {
        return $this->calculateAndSave($tramite);
    }

    /**
     * Lógica para trámites oficiales grabados en base de datos.
     */
    public function calculateAndSave(Tramite $tramite): array
    {
        return DB::transaction(function () use ($tramite) {
            // 1. Asegurar que las relaciones están cargadas
            $tramite->load(['inmuebles.municipio.provincia.departamento', 'adquirentes', 'tramiteExenciones']);
 
            // FIX: Validar que el trámite tenga inmuebles antes de calcular
            $primerInmueble = $tramite->inmuebles->first();
            if (!$primerInmueble) {
                throw new \Exception('El trámite no tiene inmuebles asociados. No se puede calcular el impuesto.');
            }

            $porcentajeParticipacion = $tramite->adquirentes->sum('porcentaje') ?: 100;

            $adquirentesData = $tramite->adquirentes->map(function ($adq) {
                return [
                    'parentesco_id' => $adq->parentesco_id,
                    'porcentaje' => $adq->porcentaje,
                ];
            })->all();

            $exencionesData = $tramite->tramiteExenciones->map(function ($ex) {
                return [
                    'monto' => $ex->monto_aplicado,
                ];
            })->all();

            // 2. Ejecutar el cálculo CORE
            // FIX: Usar departamento_id validado desde el primer inmueble
            $departamentoId = $primerInmueble->municipio?->provincia?->departamento_id ?? 1; // Default Beni

            // FIX: Pasamos 100% de participación porque los porcentajes individuales ya definen la cuota.
            // Si pasamos $porcentajeParticipacion (suma), se aplicaría doble reducción.
            $resultados = $this->performCalculation(
                $tramite->base_imponible,
                $departamentoId,
                $tramite->tipo_transmision_id,
                $tramite->fecha_presentacion->toDateString(), // Usar fecha de registro del trámite
                $tramite->fecha_transmision->toDateString(),
                $tramite->fecha_vencimiento->toDateString(),
                $adquirentesData,
                $exencionesData,
                $tramite->tipo_contribuyente ?? 'Natural',
                100 // FIX: Siempre 100, la proporción la dan los adquirentes
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
                // FIX: Usar departamento_id validado
                $tasaModel = $this->tasaVigente(
                    $departamentoId,
                    $adq->parentesco_id,
                    $tramite->tipo_transmision_id,
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
        });
    }

    /**
     * Lógica para la Calculadora Pública (Estilo Cochabamba/Santa Cruz).
     */
    public function calculateEstimate($base, $depId, $parId, $tipoId, $fTrans, $fPres, $fVenc, $contribuyente = 'Natural', $participacion = 100, array $exenciones = []): array
    {
        return $this->performCalculation(
            $base, $depId, $tipoId, $fPres, $fTrans, $fVenc,
            [['parentesco_id' => $parId, 'porcentaje' => 100]],
            $exenciones, 
            $contribuyente, $participacion
        );
    }

    /**
     * CORE: Cálculo bajo Ley 812 con factor de participación.
     */
    private function performCalculation($base, $depId, $tipoId, $fPres, $fTrans, $fVenc, $adquirentes, $exenciones, $tipoContribuyente, $participacion): array
    {
        // 1. Aplicar Factor de Participación (Estilo Cochabamba)
        // Nota: Si es un trámite con múltiples adquirentes, $participacion suele ser la suma (ej: 100%)
        // Si es calculadora, $participacion es lo que ingresa el usuario.
        $baseImponibleParticipacion = $base * ($participacion / 100);

        // 2. Cálculo del Tributo Omitido (TO)
        $totalTasas = 0;
        $tasaAplicadaDecimal = 0;

        foreach ($adquirentes as $adq) {
            // FIX: Usar tipo_transmision_id en la búsqueda de tasa
            $tasaModel = $this->tasaVigente($depId, $adq['parentesco_id'], $tipoId, $fPres);
            $tasaVal = $tasaModel ? $tasaModel->tasa : 0;
            $tasaAplicadaDecimal = $tasaVal; // Para mostrar en el reporte

            // FIX: Aplicar el porcentaje de cada adquirente sobre la base global participada
            // Si calculateEstimate envía porcentaje=100, no afecta.
            // Si calculateAndSave envía porcentajes reales (ej: 50%), se divide correctamente.
            $baseSujeto = $baseImponibleParticipacion * ($adq['porcentaje'] / 100);
            
            $proporcional = round($baseSujeto * ($tasaVal / 100), 2);
            $totalTasas += $proporcional;
        }

        // APLICAR EXENCIONES
        $totalExenciones = 0;
        if (!empty($exenciones)) {
            $totalExenciones = array_sum(array_column($exenciones, 'monto'));
        }

        // Restamos las exenciones al impuesto determinado (Crédito Fiscal)
        // Si se debiera restar a la base, mover esta lógica antes del cálculo de tasas.
        $idtgbBase = max(0, $totalTasas - $totalExenciones);

        // 3. Variables de Mora y Actualización (Estilo Santa Cruz)
        $mantenimientoValor = 0;
        $interes = 0;
        $multaIdf = 0;
        $diasMora = 0;
        $r_interes_display = 0;

        $fechaPago = Carbon::parse($fPres)->startOfDay();
        $fechaVenc = Carbon::parse($fVenc)->startOfDay();

        // FIX: Manejo de errores en UFV (aunque UFV model devuelve 1.0, aquí aseguramos)
        try {
            $ufvVencimiento = Ufv::getValorEnFecha($fechaVenc);
            $ufvPago = Ufv::getValorEnFecha($fechaPago);
        } catch (\Exception $e) {
            // Fallback seguro si falla la DB o modelo
            $ufvVencimiento = 1.0;
            $ufvPago = 1.0;
        }

        // Validación de seguridad para división por cero
        if ($ufvVencimiento == 0) {
            $ufvVencimiento = 1.0;
        }

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

        // FIX: Usar unique ID en lugar de rand() simple
        $nroTramiteRef = 'REF-' . strtoupper(substr(uniqid(), -5)) . rand(10, 99);

        return [
            'nro_tramite' => $nroTramiteRef,
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

    private function tasaVigente($depId, $parId, $tipoId, $fecha)
    {
        // Cache key: tasa:{dep}:{par}:{tipo}:{fecha}
        // FIX: Incluir tipoId en cache key
        $cacheKey = "tasa:{$depId}:{$parId}:{$tipoId}:{$fecha}";

        return Cache::remember($cacheKey, 3600, function () use ($depId, $parId, $tipoId, $fecha) {
            return Tasa::where('departamento_id', $depId)
                ->where('parentesco_id', $parId)
                ->where(function ($q) use ($tipoId) {
                    $q->where('tipo_transmision_id', $tipoId)
                      ->orWhereNull('tipo_transmision_id');
                })
                ->where('vigente_desde', '<=', $fecha)
                ->where(fn($q) => $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $fecha))
                ->orderBy('tipo_transmision_id', 'desc') // Priorizar específica sobre NULL
                ->first();
        });
    }
}
