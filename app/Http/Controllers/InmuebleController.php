<?php

namespace App\Http\Controllers;

use App\Models\Inmueble;
use App\Models\TipoInmueble;
use App\Models\Municipio;
use App\Http\Requests\StoreInmuebleRequest;
use App\Http\Requests\UpdateInmuebleRequest;
use Illuminate\Support\Facades\DB;

class InmuebleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* ----------  LISTADO (AJAX)  ---------- */
    public function index()
    {
        $this->authorize('viewAny', Inmueble::class);
        return view('admin.inmuebles.browse');
    }

    public function list()
    {
        $this->authorize('viewAny', Inmueble::class);

        $search   = request('search');
        $paginate = request('paginate', 10);

        $data = Inmueble::with(['tipoInmueble', 'municipio.provincia.departamento'])
            ->when($search, fn($q) => $q->where('catastro', 'like', "%{$search}%")
                ->orWhere('direccion', 'like', "%{$search}%"))
            ->orderBy('catastro')
            ->paginate($paginate);

        return view('admin.inmuebles.list', compact('data'));
    }

    /* ----------  LECTURA  ---------- */
    public function show(Inmueble $inmueble)
    {
        $this->authorize('view', $inmueble);
        return view('admin.inmuebles.read', compact('inmueble'));
    }

    /* ----------  ALTA  ---------- */
    public function create()
    {
        $this->authorize('create', Inmueble::class);
        return view('admin.inmuebles.edit-add', [
            'inmueble'    => new Inmueble(),
            'tipos'       => TipoInmueble::orderBy('nombre')->get(),
            'municipios'  => Municipio::with('provincia.departamento')->orderBy('nombre')->get(),
        ]);
    }

    public function store(StoreInmuebleRequest $request)
    {
        $this->authorize('create', Inmueble::class);
        Inmueble::create($request->validated());
        return redirect()->route('admin.inmuebles.index')
            ->with(['message' => 'Inmueble creado.', 'alert-type' => 'success']);
    }

    /* ----------  EDICIÓN  ---------- */
    public function edit(Inmueble $inmueble)
    {
        $this->authorize('update', $inmueble);
        return view('admin.inmuebles.edit-add', [
            'inmueble'    => $inmueble,
            'tipos'       => TipoInmueble::orderBy('nombre')->get(),
            'municipios'  => Municipio::with('provincia.departamento')->orderBy('nombre')->get(),
        ]);
    }

    public function update(UpdateInmuebleRequest $request, Inmueble $inmueble)
    {
        $this->authorize('update', $inmueble);
        $inmueble->update($request->validated());
        return redirect()->route('admin.inmuebles.index')
            ->with(['message' => 'Inmueble actualizado.', 'alert-type' => 'success']);
    }

    /* ----------  BORRADO  ---------- */
    public function destroy(Inmueble $inmueble)
    {
        $this->authorize('delete', $inmueble);
        // Si tiene avalúos, podrías validar antes de eliminar
        if ($inmueble->avaluos()->exists()) {
            return back()->with(['message' => 'No se puede eliminar: tiene avalúos asociados.', 'alert-type' => 'error']);
        }
        $inmueble->delete();
        return redirect()->route('admin.inmuebles.index')
            ->with(['message' => 'Inmueble eliminado.', 'alert-type' => 'success']);
    }
}
