<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreTipoTransmisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', \App\Models\TipoTransmision::class);
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:50|unique:tipos_transmision,nombre',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del tipo de transmisión es obligatorio',
            'nombre.unique' => 'Este tipo de transmisión ya existe',
            'nombre.max' => 'El nombre no puede tener más de 50 caracteres',
        ];
    }
}
