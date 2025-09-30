<?php

namespace App\Http\Controllers;

use App\Models\Ufv;
use Illuminate\Http\Request;

class UfvController extends Controller
{
    public function index()
    {
        $ufvs = Ufv::orderBy('fecha', 'desc')->paginate(31); // 1 mes por página
        return view('admin.ufvs.index', compact('ufvs'));
    }

    public function create()
    {
        return view('admin.ufvs.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date|unique:ufvs,fecha',
            'valor' => 'required|numeric|min:0|max:999.99999',
        ]);

        Ufv::create($request->only('fecha', 'valor'));

        return redirect()->route('admin.ufvs.index')
            ->with(['message' => 'UFV registrada.', 'alert-type' => 'success']);
    }

    public function edit(Ufv $ufv)
    {
        return view('admin.ufvs.edit', compact('ufv'));
    }

    public function update(Request $request, Ufv $ufv)
    {
        $request->validate([
            'fecha' => 'required|date|unique:ufvs,fecha,'.$ufv->id,
            'valor' => 'required|numeric|min:0|max:999.99999',
        ]);

        $ufv->update($request->only('fecha', 'valor'));

        return redirect()->route('admin.ufvs.index')
            ->with(['message' => 'UFV actualizada.', 'alert-type' => 'success']);
    }

    public function destroy(Ufv $ufv)
    {
        $ufv->delete();
        return redirect()->route('admin.ufvs.index')
            ->with(['message' => 'UFV eliminada.', 'alert-type' => 'success']);
    }
}
