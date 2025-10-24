<?php

namespace App\Http\Controllers;

use App\Models\Avaluo;
use App\Models\Inmueble;
use App\Models\Person;
use App\Http\Requests\StoreAvaluoRequest;
use App\Http\Requests\UpdateAvaluoRequest;
use Illuminate\Support\Facades\Storage;

class AvaluoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* ----------  LISTADO (AJAX)  ---------- */
    public function index()
    {
        $this->authorize('viewAny', Avaluo::class);
        return view('admin.avaluos.browse');
    }

    public function list()
    {
        try {
            $this->authorize('viewAny', Avaluo::class);

            $search      = request('search');
            $paginate    = request('paginate', 10);
            $inmuebleId  = request('inmueble_id');

            $data = Avaluo::with(['inmueble', 'perito'])
                ->when($search, fn($q) => $q->whereHas('inmueble', fn($b) => $b->where('catastro', 'like', "%{$search}%")))
                ->when($inmuebleId, fn($q) => $q->where('inmueble_id', $inmuebleId))
                ->orderByDesc('id')
                ->paginate($paginate);

            return view('admin.avaluos.list', compact('data'));

        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /* ----------  LECTURA  ---------- */
    public function show(Avaluo $avaluo)
    {
        $this->authorize('view', $avaluo);
        return view('admin.avaluos.read', compact('avaluo'));
    }

    /* ----------  ALTA  ---------- */
    public function create()
    {
        $this->authorize('create', Avaluo::class);
        return view('admin.avaluos.edit-add', [
            'avaluo'     => new Avaluo(),
            'inmuebles'  => Inmueble::orderBy('catastro')->get(),
            'peritos'    => Person::where('person_type', 'Natural')->orderBy('first_name')->orderBy('paternal_surname')->get(),
        ]);
    }

    public function store(StoreAvaluoRequest $request)
    {
        $this->authorize('create', Avaluo::class);

        $path = null;
        if ($request->hasFile('documento')) {
            $path = $request->file('documento')->store('avaluos', 'public');
        }

        Avaluo::create(array_merge($request->validated(), [
            'documento_path' => $path,
            'created_by'     => auth()->id(),
            'updated_by'     => auth()->id(),
        ]));

        return redirect()->route('admin.avaluos.index')
            ->with(['message' => 'Avalúo creado.', 'alert-type' => 'success']);
    }

    /* ----------  EDICIÓN  ---------- */
    public function edit(Avaluo $avaluo)
    {
        $this->authorize('update', $avaluo);
        return view('admin.avaluos.edit-add', [
            'avaluo'     => $avaluo,
            'inmuebles'  => Inmueble::orderBy('catastro')->get(),
            'peritos'    => Person::where('person_type', 'Natural')->orderBy('first_name')->orderBy('paternal_surname')->get(),
        ]);
    }

    public function update(UpdateAvaluoRequest $request, Avaluo $avaluo)
    {
        $this->authorize('update', $avaluo);

        $path = $avaluo->documento_path;
        if ($request->hasFile('documento')) {
            if ($path) Storage::disk('public')->delete($path);
            $path = $request->file('documento')->store('avaluos', 'public');
        }

        $avaluo->update(array_merge($request->validated(), [
            'documento_path' => $path,
            'updated_by'     => auth()->id(),
        ]));

        return redirect()->route('admin.avaluos.index')
            ->with(['message' => 'Avalúo actualizado.', 'alert-type' => 'success']);
    }

    /* ----------  BORRADO  ---------- */
    public function destroy(Avaluo $avaluo)
    {
        $this->authorize('delete', $avaluo);

        if ($avaluo->documento_path) {
            Storage::disk('public')->delete($avaluo->documento_path);
        }
        $avaluo->delete();

        return redirect()->route('admin.avaluos.index')
            ->with(['message' => 'Avalúo eliminado.', 'alert-type' => 'success']);
    }

    /* ----------  DESCARGA DE ARCHIVO  ---------- */
    public function download(Avaluo $avaluo)
    {
        $this->authorize('view', $avaluo);

        if (!$avaluo->documento_path) {
            abort(404, 'Archivo no encontrado');
        }

        return Storage::disk('public')->download($avaluo->documento_path);
    }
}
