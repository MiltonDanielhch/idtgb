<?php

// app/Http/Requests/StoreTramiteExencionRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTramiteExencionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            $exencionId = $this->input('exencion_id'); // Retrieve the input value

            if ($tramite->exenciones()->where('exencion_id', $exencionId)->exists()) {
                $v->errors()->add('exencion_id', 'Esta exención ya fue aplicada al trámite.');
            }
        });
    }
}
