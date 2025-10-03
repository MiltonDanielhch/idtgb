<?php

// app/Http/Requests/StoreAdquirenteTramiteRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdquirenteTramiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'persona_id' => 'required|exists:people,id',
            'parentesco_id' => 'required|exists:parentescos,id',
            'porcentaje' => 'required|numeric|min:0.01|max:100',
            'es_beneficiario_exencion' => 'boolean',
            'documento_sustento_exencion' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $tramite = $this->route('tramite');
            if ($tramite->adquirentes()->where('persona_id', $this->persona_id)->exists()) {
                $v->errors()->add('persona_id', 'Esta persona ya está agregada como adquirente.');
            }
        });
    }
}
