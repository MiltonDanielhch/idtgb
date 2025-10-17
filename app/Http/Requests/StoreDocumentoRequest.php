<?php

// app/Http/Requests/StoreDocumentoRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_doc' => 'required|in:Escritura,Testamento,Partida,CI,Avaluo,Poder,Otro',
            'archivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'person_id' => 'nullable|exists:people,id',
        ];
    }
}
