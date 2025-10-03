<?php

// app/Http/Requests/StorePagoRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha_pago' => 'required|date|before_or_equal:now',
            'monto' => 'required|numeric|min:0.01',
            'codigo_barras' => 'nullable|string|max:50',
            'nro_operacion' => 'nullable|string|max:25',
            'banco' => 'nullable|string|max:30',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $tramite = $this->route('tramite');
            if ($tramite->pagos()->where('estado', 'Aplicado')->exists()) {
                $v->errors()->add('monto', 'Este trámite ya tiene un pago aplicado.');
            }
        });
    }
}
