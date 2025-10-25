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
            'nro_operacion' => 'nullable|string|max:25',
            'banco' => 'nullable|string|max:30',
        ];
    }

}
