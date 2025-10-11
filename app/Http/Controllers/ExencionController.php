<?php

namespace App\Http\Controllers;

use App\Models\Exencion;
use Illuminate\Http\Request;
use App\Http\Requests\StoreExencionRequest;
use App\Http\Requests\UpdateExencionRequest;
use Illuminate\Support\Facades\Log;

class ExencionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* ----------  LISTADO  ---------- */
    public function index()
    {
        $this->authorize('viewAny', Exencion::class);
        return view('admin.exenciones.browse');
    }

    public function list(Request $request)
    {
        $this->authorize('viewAny', Exencion::class);

        $search   = $request->get('search', '');
        $paginate = $request->get('paginate', 10);

        $exenciones = Exencion::when($search, function ($query) use ($search) {
                $query->where('nombre', 'like', '%' . $search . '%');
            })
            ->orderBy('id', 'desc')
            ->paginate($paginate);

        return view('admin.exenciones.list', compact('exenciones'));
    }

    public function show(Exencion $exencion)
    {
        $this->authorize('view', $exencion);
        return view('admin.exenciones.read', compact('exencion'));
    }

    /* ----------  ALTA  ---------- */
    public function create()
    {
        $this->authorize('create', Exencion::class);
        return view('admin.exenciones.edit-add', ['exencion' => new Exencion()]);
    }

    public function store(StoreExencionRequest $request)
    {
        Exencion::create($request->validated());

        return redirect()->route('admin.exenciones.index')
            ->with(['message' => 'Exención creada.', 'alert-type' => 'success']);
    }

    /* ----------  EDICIÓN  ---------- */
    public function edit(Exencion $exencion)
    {
        $this->authorize('update', $exencion);
        return view('admin.exenciones.edit-add', compact('exencion'));
    }

    public function update(UpdateExencionRequest $request, Exencion $exencion)
    {
        $exencion->update($request->validated());

        return redirect()->route('admin.exenciones.index')
            ->with(['message' => 'Exención actualizada.', 'alert-type' => 'success']);
    }

    /* ----------  BORRADO  ---------- */
    public function destroy(Exencion $exencion)
    {
        $this->authorize('delete', $exencion);

        // Verificar si tiene trámites asociados (pivot tramite_exenciones)
        if ($exencion->tramites()->count() > 0) {
            return redirect()->route('admin.exenciones.index')
                ->with(['message' => 'No se puede eliminar: la exención está siendo usada en trámites.', 'alert-type' => 'error']);
        }

        try {
            $exencion->delete();
            return redirect()->route('admin.exenciones.index')
                ->with(['message' => 'Exención eliminada.', 'alert-type' => 'success']);
        } catch (\Exception $e) {
            Log::error("Error al eliminar Exención #{$exencion->id}: " . $e->getMessage());
            return redirect()->route('admin.exenciones.index')
                ->with(['message' => 'Error al eliminar la exención.', 'alert-type' => 'error']);
        }
    }
}
