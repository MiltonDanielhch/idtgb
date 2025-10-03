<?php

// app/Http/Controllers/TramiteExencionController.php
namespace App\Http\Controllers;

use App\Models\Tramite;
use App\Models\Exencion;
use App\Models\TramiteExencion;
use App\Http\Requests\StoreTramiteExencionRequest;
use App\Services\IdtgbCalculator;
use Illuminate\Support\Facades\DB;

class TramiteExencionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* ---------- LISTADO (AJAX) ---------- */
    public function index(Tramite $tramite)
    {
        $this->authorize('viewAny', TramiteExencion::class);
        return view('admin.tramites.exenciones.browse', compact('tramite'));
    }

    public function list(Tramite $tramite)
    {
        $this->authorize('viewAny', TramiteExencion::class);

        $search   = request('search');
        $paginate = request('paginate', 10);

        $data = TramiteExencion::with(['exencion'])
            ->where('tramite_id', $tramite->id)
            ->when($search, fn($q) => $q->whereHas('exencion', fn($sq) => $sq->where('nombre', 'like', "%{$search}%")))
            ->orderBy('id')
            ->paginate($paginate);

        return view('admin.tramites.exenciones.list', compact('tramite', 'data'));
    }

    /* ---------- LECTURA ---------- */
    public function show(Tramite $tramite, TramiteExencion $item)
    {
        $this->authorize('view', $item);
        return view('admin.tramites.exenciones.read', compact('tramite', 'item'));
    }

    /* ---------- ALTA ---------- */
    public function create(Tramite $tramite)
    {
        $this->authorize('create', TramiteExencion::class);

        $exenciones = Exencion::whereDate('vigente_desde', '<=', now())
            ->where(fn($q) => $q->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', now()))
            ->orderBy('nombre')
            ->get();

        return view('admin.tramites.exenciones.create', compact('tramite', 'exenciones'));
    }

    public function store(StoreTramiteExencionRequest $request, Tramite $tramite)
    {
        $this->authorize('create', TramiteExencion::class);

        DB::beginTransaction();
        try {
            TramiteExencion::create([
                'tramite_id'     => $tramite->id,
                'exencion_id'    => $request->exencion_id,
                'monto_aplicado' => $request->monto_aplicado,
            ]);

            app(IdtgbCalculator::class)->calcular($tramite);

            DB::commit();

            return redirect()->route('admin.tramites.exenciones.index', $tramite)
                ->with(['message' => 'Exención aplicada.', 'alert-type' => 'success']);

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }
    }

    /* ---------- BORRADO ---------- */
    public function destroy(Tramite $tramite, TramiteExencion $item)
    {
        $this->authorize('delete', $item);

        if ($item->tramite_id !== $tramite->id) {
            abort(404);
        }

        DB::beginTransaction();
        try {
            $item->delete();
            app(IdtgbCalculator::class)->calcular($tramite);
            DB::commit();

            return redirect()->route('admin.tramites.exenciones.index', $tramite)
                ->with(['message' => 'Exención quitada.', 'alert-type' => 'success']);

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }
    }
}
