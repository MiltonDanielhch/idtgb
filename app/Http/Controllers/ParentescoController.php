<?php

namespace App\Http\Controllers;

use App\Models\Parentesco;
use Illuminate\Http\Request;

class ParentescoController extends Controller
{
    /* ----------  LISTADO (sin cambios)  ---------- */
    public function index()
    {
        return view('admin.parentescos.browse');
    }

    public function list(Request $request)
    {
        $search   = $request->get('search', '');
        $paginate = $request->get('paginate', 10);

        $parentescos = Parentesco::where('nombre', 'like', "%$search%")
            ->orderBy('id', 'desc') // ← últimos primero
            ->paginate($paginate);

        return view('admin.parentescos.list', compact('parentescos'));
    }

    public function show(Parentesco $parentesco)
    {
        return view('admin.parentescos.read', compact('parentesco'));
    }

    /* ----------  ALTA  ---------- */
    public function create()
    {
        // pasamos un modelo vacío para reutilizar la misma vista
        return view('admin.parentescos.edit_add', ['parentesco' => new Parentesco()]);
    }

    public function store(Request $request)
    {
        $request->validate(['nombre' => 'required|unique:parentescos|max:50']);
        Parentesco::create($request->only('nombre'));

        return redirect()->route('admin.parentescos.index')
            ->with(['message' => 'Parentesco creado.', 'alert-type' => 'success']);
    }

    /* ----------  EDICIÓN  ---------- */
    public function edit(Parentesco $parentesco)
    {
        return view('admin.parentescos.edit_add', compact('parentesco'));
    }

    public function update(Request $request, Parentesco $parentesco)
    {
        $request->validate(['nombre' => 'required|max:50|unique:parentescos,nombre,'.$parentesco->id]);
        $parentesco->update($request->only('nombre'));

        return redirect()->route('admin.parentescos.index')
            ->with(['message' => 'Parentesco actualizado.', 'alert-type' => 'success']);
    }

    /* ----------  BORRADO  ---------- */
    public function destroy(Parentesco $parentesco)
    {
        $parentesco->delete();
        return redirect()->route('admin.parentescos.index')
            ->with(['message' => 'Parentesco eliminado.', 'alert-type' => 'success']);
    }
}
