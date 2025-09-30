<?php

namespace App\Http\Controllers;

use App\Models\Tasa;
use App\Models\Departamento;
use App\Models\Parentesco;
use App\Models\TipoTransmision;
use Illuminate\Http\Request;

class TasaController extends Controller
{
    public function index()
    {
        $tasas = Tasa::with(['departamento', 'parentesco', 'tipoTransmision'])
                     ->orderBy('vigente_desde', 'desc')
                     ->paginate(20);
        return view('admin.tasas.index', compact('tasas'));
    }

    public function create()
    {
        $departamentos = Departamento::orderBy('nombre')->get();
        $parentescos = Parentesco::orderBy('nombre')->get();
        $tipos = TipoTransmision::orderBy('nombre')->get();
        return view('admin.tasas.create', compact('departamentos', 'parentescos', 'tipos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'departamento_id'      => 'required|exists:departamentos,id',
            'parentesco_id'        => 'required|exists:parentescos,id',
            'tipo_transmision_id'  => 'nullable|exists:tipos_transmision,id',
            'tasa'                 => 'required|numeric|min:0|max:99.99',
            'vigente_desde'        => 'required|date',
            'vigente_hasta'        => 'nullable|date|after_or_equal:vigente_desde',
        ]);

        Tasa::create($request->all());

        return redirect()->route('admin.tasas.index')
            ->with(['message' => 'Tasa creada.', 'alert-type' => 'success']);
    }

    public function edit(Tasa $tasa)
    {
        $departamentos = Departamento::orderBy('nombre')->get();
        $parentescos = Parentesco::orderBy('nombre')->get();
        $tipos = TipoTransmision::orderBy('nombre')->get();
        return view('admin.tasas.edit', compact('tasa', 'departamentos', 'parentescos', 'tipos'));
    }

    public function update(Request $request, Tasa $tasa)
    {
        $request->validate([
            'departamento_id'      => 'required|exists:departamentos,id',
            'parentesco_id'        => 'required|exists:parentescos,id',
            'tipo_transmision_id'  => 'nullable|exists:tipos_transmision,id',
            'tasa'                 => 'required|numeric|min:0|max:99.99',
            'vigente_desde'        => 'required|date',
            'vigente_hasta'        => 'nullable|date|after_or_equal:vigente_desde',
        ]);

        $tasa->update($request->all());

        return redirect()->route('admin.tasas.index')
            ->with(['message' => 'Tasa actualizada.', 'alert-type' => 'success']);
    }

    public function destroy(Tasa $tasa)
    {
        $tasa->delete();
        return redirect()->route('admin.tasas.index')
            ->with(['message' => 'Tasa eliminada.', 'alert-type' => 'success']);
    }
}
