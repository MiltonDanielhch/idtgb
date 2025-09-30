<?php

namespace App\Http\Controllers;

use App\Models\Exencion;
use Illuminate\Http\Request;

class ExencionController extends Controller
{
    public function index()
    {
        $exenciones = Exencion::orderBy('nombre')->paginate(20);
        return view('admin.exenciones.index', compact('exenciones'));
    }

    public function create()
    {
        return view('admin.exenciones.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'        => 'required|string|max:100|unique:exenciones',
            'descripcion'   => 'required|string',
            'tipo'          => 'required|in:porcentaje,monto_fijo',
            'valor'         => 'required|numeric|min:0',
            'monto_maximo'  => 'nullable|numeric|min:0',
            'vigente_desde' => 'required|date',
            'vigente_hasta' => 'nullable|date|after_or_equal:vigente_desde',
        ]);

        Exencion::create($request->all());

        return redirect()->route('admin.exenciones.index')
            ->with(['message' => 'Exención creada.', 'alert-type' => 'success']);
    }

    public function edit(Exencion $exencion)
    {
        return view('admin.exenciones.edit', compact('exencion'));
    }

    public function update(Request $request, Exencion $exencion)
    {
        $request->validate([
            'nombre'        => 'required|string|max:100|unique:exenciones,nombre,'.$exencion->id,
            'descripcion'   => 'required|string',
            'tipo'          => 'required|in:porcentaje,monto_fijo',
            'valor'         => 'required|numeric|min:0',
            'monto_maximo'  => 'nullable|numeric|min:0',
            'vigente_desde' => 'required|date',
            'vigente_hasta' => 'nullable|date|after_or_equal:vigente_desde',
        ]);

        $exencion->update($request->all());

        return redirect()->route('admin.exenciones.index')
            ->with(['message' => 'Exención actualizada.', 'alert-type' => 'success']);
    }

    public function destroy(Exencion $exencion)
    {
        $exencion->delete();
        return redirect()->route('admin.exenciones.index')
            ->with(['message' => 'Exención eliminada.', 'alert-type' => 'success']);
    }
}
