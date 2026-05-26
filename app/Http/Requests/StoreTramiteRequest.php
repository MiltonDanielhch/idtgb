<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreTramiteRequest extends FormRequest
{
    public function authorize() {
        return Gate::allows('create', \App\Models\Tramite::class);
    }

    public function rules()
    {
        return [
            'nro_tramite'         => 'nullable|string|max:15|unique:tramites',
            'fecha_presentacion'  => 'required|date',
            'tipo_transmision_id' => 'required|exists:tipos_transmision,id',
            'inmueble_id'         => 'required|exists:inmuebles,id',
            'valor_declarado'     => 'required|numeric|min:0',
            'base_imponible'      => 'required|numeric|min:0',
            'fecha_transmision'   => 'required|date',
            'observaciones'       => 'nullable|string|max:1000',
        ];
    }
}
