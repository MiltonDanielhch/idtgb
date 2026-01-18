<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateTasaRequest extends FormRequest
{
    public function authorize() { return Gate::allows('update', $this->route('tasa')); }

    public function rules()
    {
        $tasaId = $this->route('tasa')->id ?? null;

        return [
            'departamento_id'      => 'required|exists:departamentos,id',
            'parentesco_id'        => 'required|exists:parentescos,id',
            'tipo_transmision_id'  => 'nullable|exists:tipos_transmision,id',
            'tasa'                 => ['required', 'numeric', 'min:0', 'max:99.99'],
            'vigente_desde'        => ['required', 'date', Rule::unique('tasas')->ignore($tasaId)->where(function ($query) {
                $query->where('departamento_id', $this->departamento_id)
                      ->where('parentesco_id', $this->parentesco_id)
                      ->where('tipo_transmision_id', $this->tipo_transmision_id ?? null);
            })],
            'vigente_hasta'        => 'nullable|date|after_or_equal:vigente_desde',
        ];
    }
}
