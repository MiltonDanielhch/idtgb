<?php

namespace App\Http\Controllers;

use App\Models\TipoInmueble;
use App\Http\Requests\StoreTipoInmuebleRequest;
use App\Http\Requests\UpdateTipoInmuebleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        $data = TipoInmueble::with(['createdBy'])->withCount('inmuebles')
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
        DB::beginTransaction();
        try {
            TipoInmueble::create($request->validated());
            DB::commit();
            return redirect()->route('admin.tipos-inmueble.index')
                ->with(['message' => 'Tipo de Inmueble creado exitosamente.', 'alert-type' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear TipoInmueble: ' . $e->getMessage());
            return back()->with(['message' => 'Error al crear el tipo de inmueble.', 'alert-type' => 'error']);
        }
    }

    public function show(TipoInmueble $tipoInmueble)
    {
        $this->authorize('view', $tipoInmueble);
        $tipoInmueble->load(['createdBy', 'updatedBy', 'inmuebles']);
        return view('admin.tipos-inmueble.read', compact('tipoInmueble'));
    }

    public function edit(TipoInmueble $tipoInmueble)
    {
        $this->authorize('update', $tipoInmueble);
        return view('admin.tipos-inmueble.edit-add', compact('tipoInmueble'));
    }

    public function update(UpdateTipoInmuebleRequest $request, TipoInmueble $tipoInmueble)
    {
        DB::beginTransaction();
        try {
            $tipoInmueble->update($request->validated());
            DB::commit();
            return redirect()->route('admin.tipos-inmueble.index')
                ->with(['message' => 'Tipo de Inmueble actualizado exitosamente.', 'alert-type' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar TipoInmueble ID ' . $tipoInmueble->id . ': ' . $e->getMessage());
            return back()->with(['message' => 'Error al actualizar el tipo de inmueble.', 'alert-type' => 'error']);
        }
    }

    public function destroy(TipoInmueble $tipoInmueble)
    {
        $this->authorize('delete', $tipoInmueble);

        if ($tipoInmueble->inmuebles()->exists()) {
            Log::warning('Intento de eliminar TipoInmueble ID ' . $tipoInmueble->id . ' con inmuebles asociados');
            return back()->with([
                'message' => 'No se puede eliminar. El tipo de inmueble está siendo utilizado.',
                'alert-type' => 'error'
            ]);
        }

        DB::beginTransaction();
        try {
            $tipoInmueble->delete();
            DB::commit();
            return redirect()->route('admin.tipos-inmueble.index')
                ->with(['message' => 'Tipo de Inmueble eliminado.', 'alert-type' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar TipoInmueble ID ' . $tipoInmueble->id . ': ' . $e->getMessage());
            return back()->with(['message' => 'Error al eliminar el tipo de inmueble.', 'alert-type' => 'error']);
        }
    }
}

