<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreParentescoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', \App\Models\Parentesco::class);
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:50|unique:parentescos,nombre',
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
