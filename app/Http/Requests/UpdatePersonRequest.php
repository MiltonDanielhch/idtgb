<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdatePersonRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('update', $this->route('person'));
    }

    public function rules()
    {
        $id = $this->route('person')->id;
        return [
            'person_type'         => 'required|in:Natural,Jurídica',
            'tipo_doc'            => 'required|in:CI,NIT,PASS',
            'ci'                  => "nullable|max:20|unique:people,ci,$id",
            'ci_complemento'      => 'nullable|max:5',
            'nit'                 => "nullable|max:20|unique:people,nit,$id",
            'legal_name'          => 'nullable|max:100',
            'first_name'          => 'nullable|max:50',
            'middle_name'         => 'nullable|max:50',
            'paternal_surname'    => 'nullable|max:50',
            'maternal_surname'    => 'nullable|max:50',
            'birth_date'          => 'nullable|date',
            'email'               => 'nullable|email|max:100',
            'phone'               => 'nullable|max:50',
            'address'             => 'nullable|max:255',
            'gender'              => 'nullable|in:Masculino,Femenino',
            'image'               => 'nullable|image|max:2048',
            'status'              => 'nullable|in:0,1,2',
            'estado_persona'      => 'nullable|in:Activo,Inactivo,Fallecido',
        ];
    }
}
