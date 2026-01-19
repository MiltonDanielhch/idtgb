<?php

namespace App\Http\Controllers;

use App\Models\Tramite;
use App\Models\Inmueble;
use App\Models\TramiteInmueble;
use App\Services\IdtgbCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TramiteInmuebleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* ----------  LISTADO (AJAX)  ---------- */
    public function index(Tramite $tramite)
    {
        $this->authorize('viewAny', TramiteInmueble::class);
        return view('admin.tramites.inmuebles.browse', compact('tramite'));
    }

    public function list(Tramite $tramite)
    {
        $this->authorize('viewAny', TramiteInmueble::class);

        $search   = request('search');
        $paginate = request('paginate', 10);

        $data = TramiteInmueble::with(['inmueble'])
            ->where('tramite_id', $tramite->id)
            ->when($search, fn($q) => $q->whereHas('inmueble', fn($sq) => $sq->where('catastro', 'like', "%{$search}%")))
            ->orderBy('id')
            ->paginate($paginate);

        return view('admin.tramites.inmuebles.list', compact('tramite', 'data'));
    }

    /* ----------  LECTURA  ---------- */
    public function show(Tramite $tramite, TramiteInmueble $item)
    {
        $this->authorize('view', $item);
        return view('admin.tramites.inmuebles.read', compact('tramite', 'item'));
    }

    /* ----------  ALTA  ---------- */
    public function create(Tramite $tramite)
    {
        $this->authorize('create', TramiteInmueble::class);

        $inmuebles = Inmueble::whereDoesntHave('tramiteInmuebles', fn($q) => $q->where('tramite_id', $tramite->id))
            ->orderBy('catastro')
            ->get();

        return view('admin.tramites.inmuebles.create', compact('tramite', 'inmuebles'));
    }

    public function store(Request $request, Tramite $tramite)
    {
        $this->authorize('create', TramiteInmueble::class);

        $request->validate([
            'inmueble_id' => 'required|exists:inmuebles,id|unique:tramite_inmuebles,tramite_id,NULL,id,inmueble_id,'.$request->inmueble_id.',tramite_id,'.$tramite->id,
        ], [
            'inmueble_id.unique' => 'El inmueble ya fue agregado a este trámite.',
        ]);

        DB::beginTransaction();
        try {
            TramiteInmueble::create([
                'tramite_id'  => $tramite->id,
                'inmueble_id' => $request->inmueble_id,
            ]);

            // FIX: Recalcular impuesto al agregar inmueble
            app(IdtgbCalculator::class)->calcular($tramite);

            DB::commit();

            return redirect()->route('admin.tramites.inmuebles.index', $tramite)
                ->with(['message' => 'Inmueble agregado al trámite.', 'alert-type' => 'success']);

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }
    }

    /* ----------  BORRADO  ---------- */
    public function destroy(Tramite $tramite, TramiteInmueble $item)
    {
        $this->authorize('delete', $item);

        // FIX: Validar que el item pertenezca al tramite
        if ($item->tramite_id !== $tramite->id) {
            abort(404);
        }

        DB::beginTransaction();
        try {
            $item->delete();

            // FIX: Recalcular impuesto al eliminar inmueble
            app(IdtgbCalculator::class)->calcular($tramite);

            DB::commit();

            return redirect()->route('admin.tramites.inmuebles.index', $tramite)
                ->with(['message' => 'Inmueble quitado del trámite.', 'alert-type' => 'success']);

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }
    }
}
