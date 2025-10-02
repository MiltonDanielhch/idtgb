<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreTasaRequest extends FormRequest
{
    public function authorize() {
        return Gate::allows('create', \App\Models\Tasa::class);
    }

    public function rules()
    {
        return [
            'departamento_id'      => 'required|exists:departamentos,id',
            'parentesco_id'        => 'required|exists:parentescos,id',
            'tipo_transmision_id'  => 'nullable|exists:tipos_transmision,id',
            'tasa'                 => 'required|numeric|min:0|max:99.99',
            'vigente_desde'        => 'required|date',
            'vigente_hasta'        => 'nullable|date|after_or_equal:vigente_desde',
        ];
    }
}
