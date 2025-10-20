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
        $personId = $this->route('person')->id;

        $rules = [  // ✅ CORREGIDO: faltaba el array inicial
            'person_type' => 'required|in:Natural,Jurídica',
            'tipo_doc' => 'required|in:CI,NIT,PASS',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|max:50',
            'address' => 'nullable|max:255',
            'municipio_id' => 'nullable|exists:municipios,id', // ✅ AÑADIDO
            'image' => 'nullable|image|max:2048',
            'status' => 'nullable|in:0,1,2',
            'estado_persona' => 'nullable|in:Activo,Inactivo,Fallecido',
            'ci_complemento' => 'nullable|max:5',
            'middle_name' => 'nullable|max:50',
            'maternal_surname' => 'nullable|max:50',
        ];

        // Reglas condicionales según tipo de persona
        if ($this->person_type === 'Jurídica') {
            $rules['nit'] = 'required|max:20|unique:people,nit,' . $personId;
            $rules['legal_name'] = 'required|max:100';

            // Campos que no aplican para jurídica
            $rules['ci'] = 'nullable|max:20|unique:people,ci,' . $personId;
            $rules['first_name'] = 'nullable|max:50';
            $rules['paternal_surname'] = 'nullable|max:50';
        } else {
            $rules['ci'] = 'required|max:20|unique:people,ci,' . $personId;
            $rules['first_name'] = 'required|max:50';
            $rules['paternal_surname'] = 'required|max:50';
            $rules['birth_date'] = 'nullable|date';
            $rules['gender'] = 'nullable|in:Masculino,Femenino';

            // Campos que no aplican para natural
            $rules['nit'] = 'nullable|max:20|unique:people,nit,' . $personId;
            $rules['legal_name'] = 'nullable|max:100';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'ci.required' => 'El CI es obligatorio para personas naturales.',
            'ci.unique' => 'Este CI ya está registrado por otra persona.',
            'nit.required' => 'El NIT es obligatorio para personas jurídicas.',
            'nit.unique' => 'Este NIT ya está registrado por otra persona.',
            'first_name.required' => 'El primer nombre es obligatorio para personas naturales.',
            'paternal_surname.required' => 'El apellido paterno es obligatorio para personas naturales.',
            'legal_name.required' => 'La razón social es obligatoria para personas jurídicas.',
            'image.image' => 'El archivo debe ser una imagen válida.',
            'image.max' => 'La imagen no debe pesar más de 2MB.',
            'municipio_id.exists' => 'El municipio seleccionado no es válido.', // ✅ AÑADIDO
        ];
    }

    public function attributes()
    {
        return [
            'person_type' => 'tipo de persona',
            'tipo_doc' => 'tipo de documento',
            'ci' => 'carnet de identidad',
            'nit' => 'NIT',
            'legal_name' => 'razón social',
            'first_name' => 'primer nombre',
            'middle_name' => 'segundo nombre',
            'paternal_surname' => 'apellido paterno',
            'maternal_surname' => 'apellido materno',
            'birth_date' => 'fecha de nacimiento',
            'estado_persona' => 'estado de persona',
            'municipio_id' => 'municipio', // ✅ AÑADIDO
        ];
    }
}
