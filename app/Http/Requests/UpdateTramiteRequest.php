<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateTramiteRequest extends FormRequest
{
    public function authorize() {
        return Gate::allows('update', $this->route('tramite'));
    }

    public function rules()
    {
        $id = $this->route('tramite')->id;
        return [
            'nro_tramite'        => "required|string|max:15|unique:tramites,nro_tramite,$id",
            'fecha_presentacion' => 'required|date',
            'tipo_transmision_id'=> 'required|exists:tipos_transmision,id',
            'valor_declarado'    => 'required|numeric|min:0',
            'base_imponible'     => 'required|numeric|min:0',
            'fecha_transmision'  => 'required|date',
            'observaciones'      => 'nullable|string|max:1000',
            'estado'             => 'in:Borrador,Pagado,Observado,Anulado,Finalizado',
        ];
    }
}
