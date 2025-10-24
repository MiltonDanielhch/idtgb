<?php

namespace App\Http\Controllers;

use App\Models\Tasa;
use App\Models\Departamento;
use App\Models\Parentesco;
use App\Models\TipoTransmision;
use App\Http\Requests\StoreTasaRequest;
use App\Http\Requests\UpdateTasaRequest;
use Illuminate\Support\Facades\DB;

class TasaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* ----------  LISTADO (AJAX)  ---------- */
    public function index()
    {
        $this->authorize('viewAny', Tasa::class);
        return view('admin.tasas.browse');
    }

    public function list()
    {
        $this->authorize('viewAny', Tasa::class);

        $search   = request('search');
        $paginate = request('paginate', 10);

        $data = Tasa::with(['departamento', 'parentesco', 'tipoTransmision'])
            ->when($search, fn($q) => $q->whereHas('departamento', fn($b) => $b->where('nombre', 'like', "%{$search}%"))
                ->orWhereHas('parentesco', fn($b) => $b->where('nombre', 'like', "%{$search}%"))
                ->orWhere('tasa', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->paginate($paginate);

        return view('admin.tasas.list', compact('data'));
    }

    /* ----------  LECTURA  ---------- */
    public function show(Tasa $tasa)
    {
        $this->authorize('view', $tasa);
        return view('admin.tasas.read', compact('tasa'));
    }

    /* ----------  ALTA  ---------- */
    public function create()
    {
        $this->authorize('create', Tasa::class);
        return view('admin.tasas.edit-add', [
            'tasa' => new Tasa(),
            'departamentos' => Departamento::orderBy('nombre')->get(),
            'parentescos'   => Parentesco::orderBy('nombre')->get(),
            'tipos'         => TipoTransmision::orderBy('nombre')->get(),
        ]);
    }

    public function store(StoreTasaRequest $request)
    {
        $this->authorize('create', Tasa::class);
        Tasa::create($request->validated());
        return redirect()->route('admin.tasas.index')
            ->with(['message' => 'Tasa creada.', 'alert-type' => 'success']);
    }

    /* ----------  EDICIÓN  ---------- */
    public function edit(Tasa $tasa)
    {
        $this->authorize('update', $tasa);
        return view('admin.tasas.edit-add', [
            'tasa' => $tasa,
            'departamentos' => Departamento::orderBy('nombre')->get(),
            'parentescos'   => Parentesco::orderBy('nombre')->get(),
            'tipos'         => TipoTransmision::orderBy('nombre')->get(),
        ]);
    }

    public function update(UpdateTasaRequest $request, Tasa $tasa)
    {
        $this->authorize('update', $tasa);
        $tasa->update($request->validated());
        return redirect()->route('admin.tasas.index')
            ->with(['message' => 'Tasa actualizada.', 'alert-type' => 'success']);
    }

    /* ----------  BORRADO  ---------- */
    public function destroy(Tasa $tasa)
    {
        $this->authorize('delete', $tasa);
        $tasa->delete();
        return redirect()->route('admin.tasas.index')
            ->with(['message' => 'Tasa eliminada.', 'alert-type' => 'success']);
    }
}
