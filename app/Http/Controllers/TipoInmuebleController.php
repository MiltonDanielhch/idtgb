<?php

namespace App\Http\Controllers;

use App\Models\TipoInmueble;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TipoInmuebleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // En el futuro, aquí se pueden añadir autorizaciones con Policies
        return view('admin.tipos-inmueble.browse');
    }

    public function list()
    {
        $search = request('search');
        $paginate = request('paginate', 10);

        $data = TipoInmueble::when($search, fn($q) => $q->where('nombre', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->paginate($paginate);

        return view('admin.tipos-inmueble.list', compact('data'));
    }

    public function create()
    {
        return view('admin.tipos-inmueble.edit-add');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:50|unique:tipos_inmueble,nombre',
        ]);

        TipoInmueble::create($validated);

        return redirect()->route('admin.tipos-inmueble.index')
            ->with(['message' => 'Tipo de Inmueble creado exitosamente.', 'alert-type' => 'success']);
    }

    public function show(TipoInmueble $tipoInmueble)
    {
        return view('admin.tipos-inmueble.read', compact('tipoInmueble'));
    }

    public function edit(TipoInmueble $tipoInmueble)
    {
        return view('admin.tipos-inmueble.edit-add', compact('tipoInmueble'));
    }

    public function update(Request $request, TipoInmueble $tipoInmueble)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:50|unique:tipos_inmueble,nombre,' . $tipoInmueble->id,
        ]);

        $tipoInmueble->update($validated);

        return redirect()->route('admin.tipos-inmueble.index')
            ->with(['message' => 'Tipo de Inmueble actualizado exitosamente.', 'alert-type' => 'success']);
    }

    public function destroy(TipoInmueble $tipoInmueble)
    {
        // Verificar si está en uso antes de borrar
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

