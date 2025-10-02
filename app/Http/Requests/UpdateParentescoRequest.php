<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;

class UpdateParentescoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('parentesco'));
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:50',
                Rule::unique('parentescos')->ignore($this->route('parentesco'))
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del parentesco es obligatorio',
            'nombre.unique' => 'Este parentesco ya existe',
            'nombre.max' => 'El nombre no puede tener más de 50 caracteres',
        ];
    }
}
