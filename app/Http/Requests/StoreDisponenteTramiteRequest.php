<?php

// app/Http/Requests/StoreDisponenteTramiteRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDisponenteTramiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'person_id' => 'required|exists:people,id',
            'tipo' => 'required|in:Causante,Donante,Testador',
            'fecha_fallecimiento' => 'nullable|date|before_or_equal:today',
            'es_discapacitado' => 'boolean',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $tramite = $this->route('tramite');
            if ($tramite->disponentes()->where('person_id', $this->person_id)->exists()) {
                $v->errors()->add('person_id', 'Esta persona ya está agregada como disponente.');
            }
        });
    }
}
