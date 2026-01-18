<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateTipoInmuebleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('tipoInmueble'));
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:50',
                Rule::unique('tipos_inmueble')->ignore($this->route('tipoInmueble'))
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del tipo de inmueble es obligatorio',
            'nombre.unique' => 'Este tipo de inmueble ya existe',
            'nombre.max' => 'El nombre no puede tener más de 50 caracteres',
        ];
    }
}
