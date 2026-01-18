<?php

namespace App\Http\Controllers;

use App\Models\Parentesco;
use Illuminate\Http\Request;
use App\Http\Requests\StoreParentescoRequest; // Nuevo
use App\Http\Requests\UpdateParentescoRequest; // Nuevo
use Illuminate\Support\Facades\Log; // Agregado para posibles logs

class ParentescoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* ----------  LISTADO  ---------- */
    public function index()
    {
        $this->authorize('viewAny', Parentesco::class);
        return view('admin.parentescos.browse');
    }


    public function list(Request $request)
    {
        $this->authorize('viewAny', Parentesco::class);

        $search   = $request->get('search', '');
        $paginate = $request->get('paginate', 10);

        $parentescos = Parentesco::withCount(['tasas', 'adquirentesTramite'])
            ->when($search, function ($query) use ($search) {
                $query->where('nombre', 'like', '%' . $search . '%');
            })
            ->orderBy('id', 'desc')
            ->paginate($paginate);

        return view('admin.parentescos.list', compact('parentescos'));
    }
    public function show(Parentesco $parentesco)
    {
        $this->authorize('view', $parentesco);
        return view('admin.parentescos.read', compact('parentesco'));
    }

    /* ----------  ALTA  ---------- */
    public function create()
    {
        $this->authorize('create', Parentesco::class);
        return view('admin.parentescos.edit_add', ['parentesco' => new Parentesco()]);
    }

    // Uso del nuevo Form Request para manejar la validación
    public function store(StoreParentescoRequest $request)
    {
        // $request->validated() contiene solo los datos validados
        Parentesco::create($request->validated());

        return redirect()->route('admin.parentescos.index')
            ->with(['message' => 'Parentesco creado.', 'alert-type' => 'success']);
    }

    /* ----------  EDICIÓN  ---------- */
    public function edit(Parentesco $parentesco)
    {
        $this->authorize('update', $parentesco);
        return view('admin.parentescos.edit_add', compact('parentesco'));
    }

    // Uso del nuevo Form Request para manejar la validación única
    public function update(UpdateParentescoRequest $request, Parentesco $parentesco)
    {
        // $request->validated() contiene solo los datos validados
        $parentesco->update($request->validated());

        return redirect()->route('admin.parentescos.index')
            ->with(['message' => 'Parentesco actualizado.', 'alert-type' => 'success']);
    }

    /* ----------  BORRADO (Dependency Check Agregado)  ---------- */
    public function destroy(Parentesco $parentesco)
    {
        $this->authorize('delete', $parentesco);

        if ($parentesco->tasas()->exists()) {
            return redirect()->route('admin.parentescos.index')
                ->with(['message' => 'No se puede eliminar: El parentesco tiene tasas asociadas.', 'alert-type' => 'error']);
        }

        if ($parentesco->adquirentesTramite()->exists()) {
            return redirect()->route('admin.parentescos.index')
                ->with(['message' => 'No se puede eliminar: El parentesco está siendo utilizado en trámites existentes.', 'alert-type' => 'error']);
        }

        try {
            $parentesco->delete();
            return redirect()->route('admin.parentescos.index')
                ->with(['message' => 'Parentesco eliminado.', 'alert-type' => 'success']);
        } catch (\Exception $e) {
            Log::error("Error al eliminar Parentesco #{$parentesco->id}: " . $e->getMessage());
            return redirect()->route('admin.parentescos.index')
                ->with(['message' => 'Ocurrió un error inesperado al intentar eliminar el parentesco.', 'alert-type' => 'error']);
        }
    }

}
