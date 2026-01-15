<?php

namespace App\Services;

use App\Models\Tramite;
use App\Models\Tasa;
use App\Models\Ufv;
use Carbon\Carbon;

class IdtgbCalculator
{
    /**
     * Calcula el IDTGB para un trámite existente y actualiza la BD.
     */
    public function calculateAndSave(Tramite $tramite): array
    {
        $inmueble = $tramite->inmuebles->first();
        if (!$inmueble || !$inmueble->municipio) {
            throw new \Exception("El trámite no tiene un inmueble con municipio asociado.");
        }

        $adquirentesData = $tramite->adquirentes->map(function ($adq) {
            return [
                'parentesco_id' => $adq->parentesco_id,
                'porcentaje' => $adq->porcentaje,
            ];
        })->all();

        $exencionesData = $tramite->tramiteExenciones->map(function ($te) {
            return [
                'tipo' => $te->exencion->tipo,
                'valor' => $te->exencion->valor,
                'monto_maximo' => $te->exencion->monto_maximo,
            ];
        })->all();

        $resultados = $this->performCalculation(
            $tramite->base_imponible,
            $inmueble->municipio->provincia->departamento_id,
            $tramite->tipo_transmision_id,
            $tramite->fecha_presentacion ?? now()->toDateString(),
            $tramite->fecha_transmision->toDateString(),
            $tramite->fecha_vencimiento->toDateString(),
            $adquirentesData,
            $exencionesData,
            $tramite->tipo_contribuyente ?? 'Natural'
        );

        // Actualización masiva de resultados en el trámite
        $tramite->update([
            'total_idtgb'  => $resultados['idtgb_base'], // Tributo Omitido original
            'recargo_mora' => $resultados['recargo'],    // Mant. Valor + Intereses + Multa
            'ufv_aplicada' => $resultados['ufv_pago'],
            'monto_final'  => $resultados['final'],
        ]);

        return $resultados;
    }

    /**
     * Estimación para la calculadora pública.
     */
    public function calculateEstimate($base, $depId, $parId, $tipoId, $fTrans, $fPres, $fVenc, $contribuyente = 'Natural'): array
    {
        return $this->performCalculation(
            $base, $depId, $tipoId, $fPres, $fTrans, $fVenc,
            [['parentesco_id' => $parId, 'porcentaje' => 100]],
            [], $contribuyente
        );
    }

    /**
     * LÓGICA CORE: Aplicación de la Ley 812 (Bolivia)
     */
    private function performCalculation($base, $depId, $tipoId, $fPres, $fTrans, $fVenc, $adquirentes, $exenciones, $tipoContribuyente): array
    {
        // 1. Cálculo del Tributo Omitido (TO) base
        $totalTasas = 0;
        $detallesTasas = [];
        foreach ($adquirentes as $adq) {
            $tasaModel = $this->tasaVigente($depId, $adq['parentesco_id'], $fPres);
            $tasaVal = $tasaModel ? $tasaModel->tasa : 0;
            $proporcional = round($base * ($adq['porcentaje'] / 100) * ($tasaVal / 100), 2);
            $totalTasas += $proporcional;
            $detallesTasas[] = ['tasa_aplicada' => $tasaVal, 'proporcional' => $proporcional];
        }

        // 2. Aplicar Exenciones
        $totalExenciones = 0;
        foreach ($exenciones as $ex) {
            $monto = ($ex['tipo'] === 'porcentaje')
                ? ($totalTasas * ($ex['valor'] / 100))
                : $ex['valor'];
            $totalExenciones += round(min($monto, $ex['monto_maximo'] ?? $monto), 2);
        }

        $idtgbBase = max(0, $totalTasas - $totalExenciones);

        // 3. Componentes de la Deuda Tributaria (Art. 47 Ley 812)
        $mantenimientoValor = 0;
        $interes = 0;
        $multaIdf = 0;
        $diasMora = 0;

        $fechaPago = Carbon::parse($fPres)->startOfDay();
        $fechaVenc = Carbon::parse($fVenc)->startOfDay();

        // Recuperar UFVs (Importante: deben existir en tu BD)
        $ufvVencimiento = Ufv::getValorEnFecha($fechaVenc);
        $ufvPago = Ufv::getValorEnFecha($fechaPago);

        if ($fechaPago->isAfter($fechaVenc)) {
            $diasMora = $fechaPago->diffInDays($fechaVenc);

            // A. Mantenimiento de Valor (TO en UFVs)
            $tributoActualizado = $idtgbBase * ($ufvPago / $ufvVencimiento);
            $mantenimientoValor = max(0, $tributoActualizado - $idtgbBase);

            // B. Intereses Moratorios (Tasa Escalonada Ley 812)
            $aniosMora = $diasMora / 360; // Año comercial boliviano
            $r = 0.04; // 4% primeros 4 años
            if ($aniosMora > 4) $r = 0.06; // 6% del año 5 al 7
            if ($aniosMora > 7) $r = 0.10; // 10% desde el año 8

            // Fórmula: I = TO_actualizado * ((1 + r/360)^n - 1)
            $interes = $tributoActualizado * (pow(1 + ($r / 360), $diasMora) - 1);

            // C. Multa IDF (Incumplimiento de Deberes Formales)
            // Natural: 50 UFV | Jurídica: 100 UFV
            $cantUfvMulta = ($tipoContribuyente === 'Jurídica') ? 100 : 50;
            $multaIdf = $cantUfvMulta * $ufvPago;
        }

        $recargoTotal = $mantenimientoValor + $interes + $multaIdf;
        $final = $idtgbBase + $recargoTotal;

        return [
            'base' => $base,
            'idtgb_base' => round($idtgbBase, 2), // S900
            'mantenimiento_valor' => round($mantenimientoValor, 2), // S920
            'interes' => round($interes, 2), // S930
            'multa_idf' => round($multaIdf, 2), // S900 (Multa)
            'recargo' => round($recargoTotal, 2),
            'final' => round($final, 2),
            'dias_mora' => $diasMora,
            'ufv_vencimiento' => $ufvVencimiento,
            'ufv_pago' => $ufvPago,
            'detalles_tasas' => $detallesTasas,
            'fecha_transmision' => Carbon::parse($fTrans)->format('d/m/Y'),
            'fecha_vencimiento' => $fechaVenc->format('d/m/Y'),
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
