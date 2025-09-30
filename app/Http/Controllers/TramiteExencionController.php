<?php

namespace App\Http\Controllers;

use App\Models\Tramite;
use App\Models\Exencion;
use App\Models\TramiteExencion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TramiteExencionController extends Controller
{
    /* ---------- LISTAR EXENCIONES APLICADAS AL TRÁMITE ---------- */
    public function index(Tramite $tramite)
    {
        // $tramite->exenciones ya devuelve instancias de Exención (pivot incluido)
        $aplicadas = $tramite->exenciones;

        return view('admin.tramites.exenciones.index', compact('tramite', 'aplicadas'));
    }

    /* ---------- FORMULARIO PARA APLICAR UNA NUEVA EXENCIÓN ---------- */
    public function create(Tramite $tramite)
    {
        $exenciones = Exencion::where('vigente_desde', '<=', now())
                              ->where(function ($q) {
                                  $q->whereNull('vigente_hasta')
                                    ->orWhere('vigente_hasta', '>=', now());
                              })
                              ->orderBy('nombre')
                              ->get();

        return view('admin.tramites.exenciones.create', compact('tramite', 'exenciones'));
    }

    /* ---------- GUARDAR NUEVA EXENCIÓN APLICADA ---------- */
    public function store(Request $request, Tramite $tramite)
    {
        $request->validate([
            'exencion_id'    => 'required|exists:exenciones,id',
            'monto_aplicado' => 'required|numeric|min:0',
        ]);

        // Evitar duplicados
        if ($tramite->exenciones()->where('exencion_id', $request->exencion_id)->exists()) {
            return back()->withErrors(['exencion_id' => 'Esta exención ya fue aplicada.']);
        }

        TramiteExencion::create([
            'tramite_id'     => $tramite->id,
            'exencion_id'    => $request->exencion_id,
            'monto_aplicado' => $request->monto_aplicado,
        ]);

        // Recalcular el IDTGB del trámite
        app(\App\Services\IdtgbCalculator::class)->calcular($tramite);

        return redirect()->route('admin.tramites.exenciones.index', $tramite)
            ->with(['message' => 'Exención aplicada.', 'alert-type' => 'success']);
    }

    /* ---------- QUITAR EXENCIÓN APLICADA ---------- */
    public function destroy(Tramite $tramite, TramiteExencion $exencion)
    {
        // Asegurar que pertenece al trámite
        if ($exencion->tramite_id !== $tramite->id) {
            abort(404);
        }

        $exencion->delete();

        // Recalcular el IDTGB del trámite
        app(\App\Services\IdtgbCalculator::class)->calcular($tramite);

        return redirect()->route('admin.tramites.exenciones.index', $tramite)
            ->with(['message' => 'Exención quitada.', 'alert-type' => 'success']);
    }
}
