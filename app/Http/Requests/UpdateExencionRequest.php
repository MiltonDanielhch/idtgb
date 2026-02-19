<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateExencionRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('update', $this->route('exencion'));
    }

    public function rules()
    {
        return [
            'nombre' => 'required|string|max:255|unique:exenciones,nombre,' . $this->route('exencion')->id,
            'tipo' => 'required|in:porcentaje,monto_fijo',
            'valor' => 'required|numeric|min:0',
            'monto_maximo' => 'nullable|numeric|min:0',
            'vigente_desde' => 'required|date',
            'vigente_hasta' => 'nullable|date|after_or_equal:vigente_desde',
        ];
    }
}
