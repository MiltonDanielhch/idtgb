<?php

namespace App\Http\Controllers;

use App\Models\TipoInmueble;
use App\Http\Requests\StoreTipoInmuebleRequest;
use App\Http\Requests\UpdateTipoInmuebleRequest;
use Illuminate\Http\Request;

class TipoInmuebleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->authorize('viewAny', TipoInmueble::class);
        return view('admin.tipos-inmueble.browse');
    }

    public function list()
    {
        $this->authorize('viewAny', TipoInmueble::class);
        $search = request('search');
        $paginate = request('paginate', 10);

        $data = TipoInmueble::withCount('inmuebles')
            ->when($search, fn($q) => $q->where('nombre', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->paginate($paginate);

        return view('admin.tipos-inmueble.list', compact('data'));
    }

    public function create()
    {
        $this->authorize('create', TipoInmueble::class);
        return view('admin.tipos-inmueble.edit-add');
    }

    public function store(StoreTipoInmuebleRequest $request)
    {
        TipoInmueble::create($request->validated());

        return redirect()->route('admin.tipos-inmueble.index')
            ->with(['message' => 'Tipo de Inmueble creado exitosamente.', 'alert-type' => 'success']);
    }

    public function show(TipoInmueble $tipoInmueble)
    {
        $this->authorize('view', $tipoInmueble);
        return view('admin.tipos-inmueble.read', compact('tipoInmueble'));
    }

    public function edit(TipoInmueble $tipoInmueble)
    {
        $this->authorize('update', $tipoInmueble);
        return view('admin.tipos-inmueble.edit-add', compact('tipoInmueble'));
    }

    public function update(UpdateTipoInmuebleRequest $request, TipoInmueble $tipoInmueble)
    {
        $tipoInmueble->update($request->validated());

        return redirect()->route('admin.tipos-inmueble.index')
            ->with(['message' => 'Tipo de Inmueble actualizado exitosamente.', 'alert-type' => 'success']);
    }

    public function destroy(TipoInmueble $tipoInmueble)
    {
        $this->authorize('delete', $tipoInmueble);

        if ($tipoInmueble->inmuebles()->exists()) {
            return back()->with([
                'message' => 'No se puede eliminar. El tipo de inmueble está siendo utilizado.',
                'alert-type' => 'error'
            ]);
        }

        $tipoInmueble->delete();

        return redirect()->route('admin.tipos-inmueble.index')
            ->with(['message' => 'Tipo de Inmueble eliminado.', 'alert-type' => 'success']);
    }
}

