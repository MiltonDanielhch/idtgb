<?php

namespace App\Http\Controllers;

use App\Models\Parentesco;
use Illuminate\Http\Request;

class ParentescoController extends Controller
{
    public function index()
    {
        $parentescos = Parentesco::paginate(20);
        return view('admin.parentescos.index', compact('parentescos'));
    }

    public function create()
    {
        return view('admin.parentescos.create');
    }

    public function store(Request $request)
    {
        $request->validate(['nombre' => 'required|unique:parentescos|max:50']);
        Parentesco::create($request->only('nombre'));

        return redirect()->route('admin.parentescos.index')
            ->with(['message' => 'Parentesco creado.', 'alert-type' => 'success']);
    }

    public function edit(Parentesco $parentesco)
    {
        return view('admin.parentescos.edit', compact('parentesco'));
    }

    public function update(Request $request, Parentesco $parentesco)
    {
        $request->validate(['nombre' => 'required|max:50|unique:parentescos,nombre,'.$parentesco->id]);
        $parentesco->update($request->only('nombre'));

        return redirect()->route('admin.parentescos.index')
            ->with(['message' => 'Parentesco actualizado.', 'alert-type' => 'success']);
    }

    public function destroy(Parentesco $parentesco)
    {
        $parentesco->delete();
        return redirect()->route('admin.parentescos.index')
            ->with(['message' => 'Parentesco eliminado.', 'alert-type' => 'success']);
    }
}
