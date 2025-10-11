<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreExencionRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('create', \App\Models\Exencion::class);

    }

    public function rules()
    {
        return [
            'nombre' => 'required|string|max:255|unique:exenciones,nombre',
            'tipo' => 'required|in:porcentaje,monto_fijo',
            'valor' => 'required|numeric|min:0',
            'monto_maximo' => 'nullable|numeric|min:0',
            'vigente_desde' => 'required|date',
            'vigente_hasta' => 'nullable|date|after_or_equal:vigente_desde',
        ];
    }
}
