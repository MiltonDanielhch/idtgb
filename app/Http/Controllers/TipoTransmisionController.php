<?php

namespace App\Http\Controllers;

use App\Models\TipoTransmision;
use App\Http\Requests\StoreTipoTransmisionRequest;
use App\Http\Requests\UpdateTipoTransmisionRequest;
use Illuminate\Support\Facades\DB;

class TipoTransmisionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->authorize('viewAny', TipoTransmision::class);
        return view('admin.tipos-transmision.browse');
    }

    public function list()
    {
        $this->authorize('viewAny', TipoTransmision::class);

        $search = request('search');
        $paginate = request('paginate', 10);

        $data = TipoTransmision::withCount(['tasas', 'tramites'])
            ->when($search, fn($q) => $q->where('nombre', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->paginate($paginate);

        return view('admin.tipos-transmision.list', compact('data'));
    }

    public function create()
    {
        $this->authorize('create', TipoTransmision::class);
        return view('admin.tipos-transmision.edit-add');
    }

    public function store(StoreTipoTransmisionRequest $request)
    {
        DB::beginTransaction();
        try {
            TipoTransmision::create($request->validated());
            DB::commit();
            return redirect()->route('admin.tipos-transmision.index')
                ->with(['message' => 'Tipo de Transmisión creado exitosamente.', 'alert-type' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with(['message' => 'Error al crear el tipo de transmisión.', 'alert-type' => 'error']);
        }
    }

    public function show(TipoTransmision $tipoTransmision)
    {
        $this->authorize('view', $tipoTransmision);
        $tipoTransmision->load(['createdBy', 'updatedBy', 'tasas', 'tramites']);
        return view('admin.tipos-transmision.read', compact('tipoTransmision'));
    }

    public function edit(TipoTransmision $tipoTransmision)
    {
        $this->authorize('update', $tipoTransmision);
        return view('admin.tipos-transmision.edit-add', compact('tipoTransmision'));
    }

    public function update(UpdateTipoTransmisionRequest $request, TipoTransmision $tipoTransmision)
    {
        DB::beginTransaction();
        try {
            $tipoTransmision->update($request->validated());
            DB::commit();
            return redirect()->route('admin.tipos-transmision.index')
                ->with(['message' => 'Tipo de Transmisión actualizado exitosamente.', 'alert-type' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with(['message' => 'Error al actualizar el tipo de transmisión.', 'alert-type' => 'error']);
        }
    }

    public function destroy(TipoTransmision $tipoTransmision)
    {
        $this->authorize('delete', $tipoTransmision);

        if ($tipoTransmision->tasas()->exists() || $tipoTransmision->tramites()->exists()) {
            return back()->with([
                'message' => 'No se puede eliminar. El tipo de transmisión está siendo utilizado en tasas o trámites.',
                'alert-type' => 'error'
            ]);
        }

        DB::beginTransaction();
        try {
            $tipoTransmision->delete();
            DB::commit();
            return redirect()->route('admin.tipos-transmision.index')
                ->with(['message' => 'Tipo de Transmisión eliminado.', 'alert-type' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with(['message' => 'Error al eliminar el tipo de transmisión.', 'alert-type' => 'error']);
        }
    }
}
