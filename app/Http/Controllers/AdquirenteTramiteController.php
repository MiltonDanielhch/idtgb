<?php

namespace App\Http\Controllers;

use App\Models\Tramite;
use App\Models\Person;
use App\Models\Parentesco;
use App\Models\AdquirenteTramite;
use App\Models\Tasa;
use Illuminate\Http\Request;

class AdquirenteTramiteController extends Controller
{
    public function index(Tramite $tramite)
    {
        $adquirentes = $tramite->adquirentes()->with(['persona', 'parentesco'])->get();
        return view('admin.tramites.adquirentes.index', compact('tramite', 'adquirentes'));
    }

    public function create(Tramite $tramite)
    {
        $personas = Person::where('person_type', 'Natural')->orderBy('first_name')->orderBy('paternal_surname')->get();
        $parentescos = Parentesco::orderBy('nombre')->get();
        return view('admin.tramites.adquirentes.create', compact('tramite', 'personas', 'parentescos'));
    }

    public function store(Request $request, Tramite $tramite)
    {
        $request->validate([
            'persona_id'                    => 'required|exists:people,id',
            'parentesco_id'                 => 'required|exists:parentescos,id',
            'porcentaje'                    => 'required|numeric|min:0.01|max:100',
            'es_beneficiario_exencion'      => 'boolean',
            'documento_sustento_exencion'   => 'nullable|file|mimes:pdf,jpg,png|max:2048',
        ]);

        // Verificar que no esté duplicado
        if ($tramite->adquirentes()->where('persona_id', $request->persona_id)->exists()) {
            return back()->withErrors(['persona_id' => 'Esta persona ya está agregada como adquirente.']);
        }

        // Calcular tasa según departamento, parentesco y tipo de transmisión
        $tasa = Tasa::where('departamento_id', $tramite->inmueble->municipio->provincia->departamento_id)
                    ->where('parentesco_id', $request->parentesco_id)
                    ->where('tipo_transmision_id', $tramite->tipo_transmision_id)
                    ->where('vigente_desde', '<=', $tramite->fecha_presentacion)
                    ->where(function ($q) use ($tramite) {
                        $q->whereNull('vigente_hasta')
                          ->orWhere('vigente_hasta', '>=', $tramite->fecha_presentacion);
                    })
                    ->first();

        $tasaAplicada = $tasa ? $tasa->tasa : 0;

        // Subir archivo de sustento si existe
        $path = null;
        if ($request->hasFile('documento_sustento_exencion')) {
            $path = $request->file('documento_sustento_exencion')->store('adquirentes/exencion', 'public');
        }

        AdquirenteTramite::create([
            'tramite_id'                    => $tramite->id,
            'persona_id'                    => $request->persona_id,
            'parentesco_id'                 => $request->parentesco_id,
            'porcentaje'                    => $request->porcentaje,
            'tasa_aplicada'                 => $tasaAplicada,
            'idtgb_proporcional'            => 0, // se calculará después
            'es_beneficiario_exencion'      => $request->boolean('es_beneficiario_exencion'),
            'documento_sustento_exencion'   => $path,
        ]);

        // Recalcular IDTGB
        app(\App\Services\IdtgbCalculator::class)->calcular($tramite);

        return redirect()->route('admin.tramites.adquirentes.index', $tramite)
            ->with(['message' => 'Adquirente agregado.', 'alert-type' => 'success']);
    }

    public function destroy(Tramite $tramite, AdquirenteTramite $adquirente)
    {
        // Asegurar que pertenece al trámite
        if ($adquirente->tramite_id !== $tramite->id) {
            abort(404);
        }

        // Borrar archivo si existe
        if ($adquirente->documento_sustento_exencion) {
            \Storage::disk('public')->delete($adquirente->documento_sustento_exencion);
        }

        $adquirente->delete();

        // Recalcular IDTGB
        app(\App\Services\IdtgbCalculator::class)->calcular($tramite);

        return redirect()->route('admin.tramites.adquirentes.index', $tramite)
            ->with(['message' => 'Adquirente quitado.', 'alert-type' => 'success']);
    }
}
