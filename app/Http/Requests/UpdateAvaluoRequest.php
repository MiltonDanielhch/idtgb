<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateAvaluoRequest extends FormRequest
{
    public function authorize() { return Gate::allows('update', $this->route('avaluo')); }

    public function rules()
    {
        return [
            'inmueble_id'    => 'required|exists:inmuebles,id',
            'tipo_avaluo'    => 'required|in:Fiscal,Comercial,Pericial',
            'fecha_avaluo'   => 'required|date',
            'valor'          => 'required|numeric|min:0',
            'perito_id'      => 'nullable|exists:people,id',
            'documento'      => 'nullable|file|mimes:pdf,jpg,png|max:5120',
            'estado'         => 'in:Vigente,Caducado',
        ];
    }
}
