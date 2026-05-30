<?php

namespace App\Services;

use App\Models\Parentesco;
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

            // MAPEO CORREGIDO SEGÚN LEY 812:
            // mantenimiento_valor ahora es 0 (va implícito en conversión UFV)
            // El recargo_mora es solo intereses + multa
            $tramite->update([
                'total_idtgb'  => $resultados['idtgb_base'], // Tributo omitido base
                'recargo_mora' => $resultados['interes'] + $resultados['multa_idf'], // Solo intereses + multa
                'monto_final'  => $resultados['final'], // Total deuda tributaria + multa
                'ufv_aplicada' => $resultados['ufv_pago'], // UFV de pago
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
     * CORE: Cálculo bajo Ley 812 con factor de participación - VERSIÓN CORREGIDA
     * Implementa cálculo puro UFV con intereses escalonados acumulativos
     */
    public function performCalculation($base, $depId, $tipoId, $fPres, $fTrans, $fVenc, $adquirentes, $exenciones, $tipoContribuyente, $participacion): array
    {
        // 1. Aplicar Factor de Participación (Estilo Cochabamba)
        $baseImponibleParticipacion = $base * ($participacion / 100);

        // 2. Cálculo del Tributo Omitido (TO) en Bolivianos
        $totalTasas = 0;
        $tasaAplicadaDecimal = 0;

        foreach ($adquirentes as $adq) {
            $tasaModel = $this->tasaVigente($depId, $adq['parentesco_id'], $tipoId, $fPres);
            $tasaVal = $tasaModel ? $tasaModel->tasa : 0;
            $tasaAplicadaDecimal = $tasaVal;

            $baseSujeto = $baseImponibleParticipacion * ($adq['porcentaje'] / 100);
            $proporcional = round($baseSujeto * ($tasaVal / 100), 2);
            $totalTasas += $proporcional;
        }

        // APLICAR EXENCIONES
        $totalExenciones = 0;
        if (!empty($exenciones)) {
            $totalExenciones = array_sum(array_column($exenciones, 'monto'));
        }

        $idtgbBase = max(0, $totalTasas - $totalExenciones);

        // 3. CÁLCULO PURO UFV SEGÚN LEY 812 (Artículo 47)
        $fechaPago = Carbon::parse($fPres)->startOfDay();
        $fechaVenc = Carbon::parse($fVenc)->startOfDay();
        $diasMora = 0;

        // Obtener valores UFV
        try {
            $ufvVencimiento = Ufv::getValorEnFecha($fechaVenc);
            $ufvPago = Ufv::getValorEnFecha($fechaPago);
        } catch (\Exception $e) {
            $ufvVencimiento = 1.0;
            $ufvPago = 1.0;
        }

        if ($ufvVencimiento == 0) $ufvVencimiento = 1.0;

        // Variables para cálculo UFV puro
        $interesTotal = 0;
        $mantenimientoValor = 0; // Ya no se usa, va implícito en conversión UFV
        $multaIdf = 0;
        $r_interes_display = 0;

        if ($fechaPago->isAfter($fechaVenc)) {
            $diasMora = $fechaPago->diffInDays($fechaVenc);

            // === CÁLCULO EN DOMINIO UFV PURO ===
            
            // Paso 1: Convertir Tributo Omitido a UFV en fecha de vencimiento
            $toUfv = $idtgbBase / $ufvVencimiento;

            // Paso 2: Cálculo de Intereses por Tramos Acumulados
            // Tramo 1: Hasta 4 años (máximo 1440 días) - Tasa 4%
            $n1 = min($diasMora, 1440);
            $i1 = $toUfv * (pow(1 + (0.04 / 360), $n1) - 1);
            $saldo1 = $toUfv + $i1;

            // Tramo 2: Años 5-7 (máximo 1080 días) - Tasa 6%
            $n2 = 0;
            $i2 = 0;
            $saldo2 = $saldo1;
            if ($diasMora > 1440) {
                $n2 = min($diasMora - 1440, 1080);
                $i2 = $saldo1 * (pow(1 + (0.06 / 360), $n2) - 1);
                $saldo2 = $saldo1 + $i2;
            }

            // Tramo 3: Año 8 en adelante - Tasa 10%
            $n3 = 0;
            $i3 = 0;
            $saldo3 = $saldo2;
            if ($diasMora > 2520) {
                $n3 = $diasMora - 2520;
                $i3 = $saldo2 * (pow(1 + (0.10 / 360), $n3) - 1);
                $saldo3 = $saldo2 + $i3;
            }

            $interesTotalUfv = $i1 + $i2 + $i3;

            // Paso 3: Deuda Tributaria en UFV
            $deudaTributariaUfv = $toUfv + $interesTotalUfv;

            // Paso 4: Convertir a Bolivianos
            $deudaTributariaBs = $deudaTributariaUfv * $ufvPago;

            // Paso 5: Calcular tributo actualizado por UFV (para mostrar en boleta)
            $tributoActualizado = $toUfv * $ufvPago;

            // Paso 6: Calcular intereses en Bs para mostrar
            $interesTotal = $deudaTributariaBs - $tributoActualizado;

            // Paso 6: Multa IDF
            $cantUfvMulta = ($tipoContribuyente === 'Jurídica') ? 100 : 50;
            $multaIdf = $cantUfvMulta * $ufvPago;

            // El mantenimiento de valor ya está implícito en la conversión UFV
            // Deuda Tributaria Bs = (TO_UFV + Intereses_UFV) * UFV_Pago
            // = TO_UFV * UFV_Pago + Intereses_UFV * UFV_Pago
            // = Tributo_Actualizado + Intereses_Bs
            // Donde Tributo_Actualizado incluye el ajuste por inflación
        } else {
            // Sin mora: solo tributo base
            $deudaTributariaBs = $idtgbBase;
        }

        $recargoTotal = $interesTotal + $multaIdf;
        $final = $deudaTributariaBs + $multaIdf;

        // Determinar tasa de interés para display (la más alta aplicada)
        if ($diasMora > 2520) $r_interes_display = 10;
        elseif ($diasMora > 1440) $r_interes_display = 6;
        elseif ($diasMora > 0) $r_interes_display = 4;

        $nroTramiteRef = 'REF-' . strtoupper(substr(uniqid(), -5)) . rand(10, 99);

        // Obtener categoría de tasa del parentesco
        $categoriaTasa = 1; // Default
        if (!empty($adquirentes) && isset($adquirentes[0]['parentesco_id'])) {
            $parentesco = Parentesco::find($adquirentes[0]['parentesco_id']);
            if ($parentesco) {
                $categoriaTasa = $parentesco->categoria_tasa ?? 1;
            }
        }

        return [
            'nro_tramite' => $nroTramiteRef,
            'base_original' => $base,
            'participacion' => $participacion,
            'base_imponible_calculada' => $baseImponibleParticipacion,
            'tasa_aplicada' => $tasaAplicadaDecimal,
            'idtgb_base' => round($idtgbBase, 2),
            'tributo_actualizado' => isset($tributoActualizado) ? round($tributoActualizado, 2) : round($idtgbBase, 2),
            'mantenimiento_valor' => isset($tributoActualizado) ? round($tributoActualizado - $idtgbBase, 2) : 0,
            'interes' => round($interesTotal, 2),
            'tasa_mora' => $r_interes_display,
            'multa_idf' => round($multaIdf, 2),
            'final' => round($final, 2),
            'dias_mora' => $diasMora,
            'ufv_vencimiento' => $ufvVencimiento,
            'ufv_pago' => $ufvPago,
            'categoria_tasa' => $categoriaTasa,
            'fecha_transmision' => Carbon::parse($fTrans)->format('d/m/Y'),
            'fecha_vencimiento' => $fechaVenc->format('d/m/Y'),
            'fecha_pago' => $fechaPago->format('d/m/Y'),
        ];
    }

    public function tasaVigente($depId, $parId, $tipoId, $fecha)
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
