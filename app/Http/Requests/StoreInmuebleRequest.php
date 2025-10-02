<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreInmuebleRequest extends FormRequest
{
    public function authorize() {
        return Gate::allows('create', \App\Models\Inmueble::class);
    }

    public function rules()
    {
        return [
            'complemento'                => 'nullable|string|max:3',
            'catastro'                   => 'required|string|max:15|unique:inmuebles',
            'tipo_inmueble_id'           => 'required|exists:tipos_inmueble,id',
            'municipio_id'               => 'nullable|exists:municipios,id',
            'barrio_comunidad'           => 'nullable|string|max:100',
            'direccion'                  => 'nullable|string|max:200',
            'superficie_m2'              => 'nullable|numeric|min:0',
            'valor_catastral'            => 'required|numeric|min:0',
            'matricula_rr'               => 'nullable|string|max:20',
            'es_vivienda_unica_familiar' => 'boolean',
            'estado_inmueble'            => 'in:Activo,Transferido,Baja',
        ];
    }
}
