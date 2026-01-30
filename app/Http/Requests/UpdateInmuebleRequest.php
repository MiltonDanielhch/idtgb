<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInmuebleRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // 'catastro' => ['required', 'string', 'max:15', Rule::unique('inmuebles')->ignore($this->route('inmueble')), 'regex:/^[A-Z0-9]{2}-\d{4}-[A-Z0-9]{2}-\d{4}$/'],
            'catastro' => [
                'required',
                'string',
                'max:20', // Aumentamos un poco por si acaso
                Rule::unique('inmuebles')->ignore($this->route('inmueble'))
            ],
            'complemento' => 'nullable|string|max:3',
            'tipo_inmueble_id' => 'required|exists:tipos_inmueble,id',
            'municipio_id' => 'nullable|exists:municipios,id',
            'barrio_comunidad' => 'nullable|string|max:100',
            'direccion' => 'nullable|string|max:200',
            'superficie_m2' => 'nullable|numeric|min:0',
            'valor_catastral' => 'required|numeric|min:0',
            'matricula_rr' => 'nullable|string|max:20',
            'es_vivienda_unica_familiar' => 'boolean',
            'estado_inmueble' => 'in:Activo,Transferido,Baja',
        ];
    }
}
