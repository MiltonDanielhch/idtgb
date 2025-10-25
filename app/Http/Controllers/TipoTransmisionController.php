<?php

namespace App\Http\Controllers;

use App\Models\TipoTransmision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TipoTransmisionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('admin.tipos-transmision.browse');
    }

    public function list()
    {
        $search = request('search');
        $paginate = request('paginate', 10);

        $data = TipoTransmision::when($search, fn($q) => $q->where('nombre', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->paginate($paginate);

        return view('admin.tipos-transmision.list', compact('data'));
    }

    public function create()
    {
        return view('admin.tipos-transmision.edit-add');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:50|unique:tipos_transmision,nombre',
        ]);

        TipoTransmision::create($validated);

        return redirect()->route('admin.tipos-transmision.index')
            ->with(['message' => 'Tipo de Transmisión creado exitosamente.', 'alert-type' => 'success']);
    }

    public function show(TipoTransmision $tipoTransmision)
    {
        return view('admin.tipos-transmision.read', compact('tipoTransmision'));
    }

    public function edit(TipoTransmision $tipoTransmision)
    {
        return view('admin.tipos-transmision.edit-add', compact('tipoTransmision'));
    }

    public function update(Request $request, TipoTransmision $tipoTransmision)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:50|unique:tipos_transmision,nombre,' . $tipoTransmision->id,
        ]);

        $tipoTransmision->update($validated);

        return redirect()->route('admin.tipos-transmision.index')
            ->with(['message' => 'Tipo de Transmisión actualizado exitosamente.', 'alert-type' => 'success']);
    }

    public function destroy(TipoTransmision $tipoTransmision)
    {
        // Verificar si está en uso antes de borrar
        if ($tipoTransmision->tasas()->exists() || $tipoTransmision->tramites()->exists()) {
            return back()->with([
                'message' => 'No se puede eliminar. El tipo de transmisión está siendo utilizado en tasas o trámites.',
                'alert-type' => 'error'
            ]);
        }

        $tipoTransmision->delete();

        return redirect()->route('admin.tipos-transmision.index')
            ->with(['message' => 'Tipo de Transmisión eliminado.', 'alert-type' => 'success']);
    }
}
