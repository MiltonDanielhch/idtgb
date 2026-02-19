<?php

// app/Http/Requests/StoreTramiteExencionRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreTramiteExencionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', \App\Models\TramiteExencion::class);
    }

    public function rules(): array
    {
        return [
            'exencion_id'    => 'required|exists:exenciones,id',
            'monto_aplicado' => 'required|numeric|min:0.01',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $tramite = $this->route('tramite');
            $exencionId = $this->input('exencion_id');
            $montoAplicado = (float) $this->input('monto_aplicado');

            // Bug #1: Validación de duplicados (previene race condition)
            if ($tramite->exenciones()->where('exencion_id', $exencionId)->exists()) {
                $v->errors()->add('exencion_id', 'Esta exención ya fue aplicada al trámite.');
                return;
            }

            $exencion = \App\Models\Exencion::find($exencionId);
            if (!$exencion) {
                return;
            }

            // Bug #2: Validar que monto_aplicado no exceda monto_maximo
            if ($exencion->monto_maximo !== null && $montoAplicado > $exencion->monto_maximo) {
                $v->errors()->add('monto_aplicado', 
                    "El monto aplicado (Bs. {$montoAplicado}) no puede exceder el monto máximo permitido (Bs. {$exencion->monto_maximo}).");
            }

            // Bug #3: Validar vigencia según fecha de presentación del trámite
            $fechaTramite = $tramite->fecha_presentacion->toDateString();
            if ($fechaTramite < $exencion->vigente_desde->toDateString()) {
                $v->errors()->add('exencion_id', 
                    "La exención no está vigente para la fecha del trámite ({$fechaTramite}). Vigente desde: {$exencion->vigente_desde->toDateString()}.");
            }
            if ($exencion->vigente_hasta !== null && $fechaTramite > $exencion->vigente_hasta->toDateString()) {
                $v->errors()->add('exencion_id', 
                    "La exención no está vigente para la fecha del trámite ({$fechaTramite}). Vigente hasta: {$exencion->vigente_hasta->toDateString()}.");
            }

            // Bug #7: Validar que la suma de exenciones no exceda el impuesto calculado
            $totalExencionesActuales = $tramite->tramiteExenciones()->sum('monto_aplicado');
            $impuestoCalculado = $tramite->total_idtgb ?? 0;
            $nuevaSuma = $totalExencionesActuales + $montoAplicado;
            
            if ($nuevaSuma > $impuestoCalculado) {
                $v->errors()->add('monto_aplicado', 
                    "La suma total de exenciones (Bs. {$nuevaSuma}) no puede exceder el impuesto calculado (Bs. {$impuestoCalculado}).");
            }
        });
    }
}
