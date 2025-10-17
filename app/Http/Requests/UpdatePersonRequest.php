<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePersonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Assuming you have a 'person' route parameter.
        // The 'update' policy method will be called on the PersonPolicy.
        return $this->user()->can('update', $this->person);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $personId = $this->route('person')->id;

        return [
            'person_type' => ['required', Rule::in(['Natural', 'Jurídica'])],
            
            'tipo_doc' => 'required|string|max:10',
            'ci' => [
                'nullable',
                'string',
                Rule::unique('people')->where(function ($query) {
                    return $query->where('tipo_doc', $this->tipo_doc)
                                 ->where('ci_complemento', $this->ci_complemento);
                })->ignore($personId),
            ],
            'ci_complemento' => 'nullable|string|max:5',
            'nit' => 'nullable|string|unique:people,nit,' . $personId,

            'first_name' => 'required_if:person_type,Natural|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'paternal_surname' => 'required_if:person_type,Natural|string|max:255',
            'maternal_surname' => 'nullable|string|max:255',
            'legal_name' => 'required_if:person_type,Jurídica|string|max:255',

            'birth_date' => 'nullable|date',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',

            'gender' => ['nullable', Rule::in(['Masculino', 'Femenino'])],
            'estado_persona' => ['nullable', Rule::in(['Activo', 'Inactivo', 'Fallecido'])],
        ];
    }
}