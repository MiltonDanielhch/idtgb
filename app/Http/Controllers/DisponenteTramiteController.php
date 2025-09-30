<?php

namespace App\Http\Controllers;

use App\Models\Tramite;
use App\Models\Person;
use App\Models\DisponenteTramite;
use Illuminate\Http\Request;

class DisponenteTramiteController extends Controller
{
    public function index(Tramite $tramite)
    {
        $disponentes = $tramite->disponentes()->with('persona')->get();
        return view('admin.tramites.disponentes.index', compact('tramite', 'disponentes'));
    }

    public function create(Tramite $tramite)
    {
        $personas = Person::where('person_type', 'Natural')->orderBy('first_name')->orderBy('paternal_surname')->get();
        return view('admin.tramites.disponentes.create', compact('tramite', 'personas'));
    }

    public function store(Request $request, Tramite $tramite)
    {
        $request->validate([
            'persona_id'          => 'required|exists:people,id',
            'tipo'                => 'required|in:Causante,Donante,Testador',
            'fecha_fallecimiento' => 'nullable|date|before_or_equal:today',
            'es_discapacitado'    => 'boolean',
        ]);

        // Verificar que no esté duplicado
        if ($tramite->disponentes()->where('persona_id', $request->persona_id)->exists()) {
            return back()->withErrors(['persona_id' => 'Esta persona ya está agregada como disponente.']);
        }

        DisponenteTramite::create([
            'tramite_id'           => $tramite->id,
            'persona_id'           => $request->persona_id,
            'tipo'                 => $request->tipo,
            'fecha_fallecimiento'  => $request->fecha_fallecimiento,
            'es_discapacitado'     => $request->boolean('es_discapacitado'),
        ]);

        return redirect()->route('admin.tramites.disponentes.index', $tramite)
            ->with(['message' => 'Disponente agregado.', 'alert-type' => 'success']);
    }

    public function destroy(Tramite $tramite, DisponenteTramite $disponente)
    {
        // Asegurar que pertenece al trámite
        if ($disponente->tramite_id !== $tramite->id) {
            abort(404);
        }

        $disponente->delete();

        return redirect()->route('admin.tramites.disponentes.index', $tramite)
            ->with(['message' => 'Disponente quitado.', 'alert-type' => 'success']);
    }
}
