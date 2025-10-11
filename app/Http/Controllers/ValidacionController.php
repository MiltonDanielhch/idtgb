<?php

namespace App\Http\Controllers;

use App\Models\Tramite;

class ValidacionController extends Controller
{
    /**
     * Muestra la información de un trámite a partir de su hash de validación.
     *
     * @param string $hash
     * @return \Illuminate\View\View
     */
    public function show(string $hash)
    {
        $tramite = Tramite::where('hash_validacion', $hash)->firstOrFail();

        return view('validacion.show', compact('tramite'));
    }
}