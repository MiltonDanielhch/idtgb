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
        // Bug #2: Validar longitud exacta de hash SHA256 (64 caracteres hexadecimales)
        if (strlen($hash) !== 64 || !ctype_xdigit($hash)) {
            abort(404, 'Hash de validación inválido');
        }

        // Bug #5: Usar scope whereHashValidacion para reutilización
        $tramite = Tramite::whereHashValidacion($hash)->firstOrFail();

        // Bug #4: Validar que el trámite no esté en estados no válidos para verificación
        if (in_array($tramite->estado, ['Borrador', 'Anulado'])) {
            abort(404, 'El trámite no está disponible para validación');
        }

        return view('validacion.show', compact('tramite'));
    }
}