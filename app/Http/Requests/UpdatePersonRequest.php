<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdatePersonRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('update', $this->route('person'));
    }

    public function rules()
    {
        $personId = $this->route('person')->id;

        $rules = [
            'person_type' => 'required|in:Natural,Jurídica',
            'tipo_doc' => 'required|in:CI,NIT',
            'phone' => 'nullable|max:50',
            'ci_complemento' => 'nullable|max:5',
        ];

        $ciRule = [
            Rule::unique('people')
                ->ignore($personId)
                ->where(function ($query) {
                    return $query->where('ci_complemento', $this->ci_complemento);
                })
        ];

        // Reglas condicionales según tipo de persona
        if ($this->person_type === 'Jurídica') {
            $rules['nit'] = 'required|max:20|unique:people,nit,' . $personId;
            $rules['legal_name'] = 'required|max:100';

            // Campos que no aplican para jurídica
            $rules['ci'] = array_merge(['nullable', 'max:20'], $ciRule);
            $rules['nombre_completo'] = 'nullable|max:200';
        } else {
            $rules['ci'] = array_merge(['required', 'max:20'], $ciRule);
            $rules['nombre_completo'] = 'required|max:200';

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
            'nombre_completo.required' => 'El nombre completo es obligatorio para personas naturales.',
            'legal_name.required' => 'La razón social es obligatoria para personas jurídicas.',
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
            'nombre_completo' => 'nombre completo',
        ];
    }
}
