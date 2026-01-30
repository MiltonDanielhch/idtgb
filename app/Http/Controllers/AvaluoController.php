<?php

namespace App\Http\Controllers;

use App\Models\Avaluo;
use App\Models\Inmueble;
use App\Models\Person;
use App\Http\Requests\StoreAvaluoRequest;
use App\Http\Requests\UpdateAvaluoRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AvaluoController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Avaluo::class);
        return view('admin.avaluos.browse');
    }

    public function list(Request $request)
    {
        $this->authorize('viewAny', Avaluo::class);

        $paginate = $request->input('paginate', 10);
        $search = $request->input('search');
        $inmueble_id = $request->input('inmueble_id');

        try {
            $query = Avaluo::with(['inmueble', 'perito', 'creador'])
                ->orderBy('id', 'DESC');

            if ($inmueble_id) {
                $query->where('inmueble_id', $inmueble_id);
            }

            if ($search) {
                $query->whereHas('inmueble', function ($q) use ($search) {
                    $q->where('catastro', 'like', "%{$search}%");
                });
            }

            $data = $query->paginate($paginate);

            return view('admin.avaluos.list', compact('data'));
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['error' => 'Ocurrió un error al cargar la lista.'], 500);
        }
    }

    public function create()
    {
        $this->authorize('create', Avaluo::class);

        $avaluo = new Avaluo();
        $inmuebles = Inmueble::all();
        $peritos = Person::where('person_type', 'Natural')->get();

        return view('admin.avaluos.edit-add', compact('avaluo', 'inmuebles', 'peritos'));
    }

    public function store(StoreAvaluoRequest $request)
    {
        $this->authorize('create', Avaluo::class);

        DB::beginTransaction();
        try {
            $data = $request->validated();

            if ($request->hasFile('documento')) {
                $file = $request->file('documento');
                $path = $file->store('avaluos', 'public');
                $data['documento_path'] = $path;
            }

            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();

            Avaluo::create($data);

            DB::commit();
            return redirect()->route('admin.avaluos.index')
                ->with(['message' => 'Avalúo registrado exitosamente.', 'alert-type' => 'success']);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);
            return back()->with(['message' => 'Ocurrió un error al guardar el avalúo.', 'alert-type' => 'error']);
        }
    }

    public function show(Avaluo $avaluo)
    {
        $this->authorize('view', $avaluo);
        $avaluo->load(['inmueble', 'perito', 'creador', 'editor']);
        return view('admin.avaluos.read', compact('avaluo'));
    }

    public function edit(Avaluo $avaluo)
    {
        $this->authorize('update', $avaluo);

        $inmuebles = Inmueble::all();
        $peritos = Person::where('person_type', 'Natural')->get();

        return view('admin.avaluos.edit-add', compact('avaluo', 'inmuebles', 'peritos'));
    }

    public function update(UpdateAvaluoRequest $request, Avaluo $avaluo)
    {
        $this->authorize('update', $avaluo);

        DB::beginTransaction();
        try {
            $data = $request->validated();

            if ($request->hasFile('documento')) {
                if ($avaluo->documento_path && Storage::disk('public')->exists($avaluo->documento_path)) {
                    Storage::disk('public')->delete($avaluo->documento_path);
                }

                $file = $request->file('documento');
                $path = $file->store('avaluos', 'public');
                $data['documento_path'] = $path;
            }

            $data['updated_by'] = auth()->id();

            $avaluo->update($data);

            DB::commit();
            return redirect()->route('admin.avaluos.index')
                ->with(['message' => 'Avalúo actualizado exitosamente.', 'alert-type' => 'success']);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);
            return back()->with(['message' => 'Ocurrió un error al actualizar el avalúo.', 'alert-type' => 'error']);
        }
    }

    public function destroy(Avaluo $avaluo)
    {
        $this->authorize('delete', $avaluo);

        DB::beginTransaction();
        try {
            if ($avaluo->documento_path && Storage::disk('public')->exists($avaluo->documento_path)) {
                Storage::disk('public')->delete($avaluo->documento_path);
            }

            $avaluo->delete();

            DB::commit();
            return redirect()->route('admin.avaluos.index')
                ->with(['message' => 'Avalúo eliminado exitosamente.', 'alert-type' => 'success']);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);
            return back()->with(['message' => 'Ocurrió un error al eliminar el avalúo.', 'alert-type' => 'error']);
        }
    }

    public function download(Avaluo $avaluo)
    {
        $this->authorize('view', $avaluo);

        if (!$avaluo->documento_path || !Storage::disk('public')->exists($avaluo->documento_path)) {
            return back()->with(['message' => 'El archivo no existe.', 'alert-type' => 'error']);
        }

        return Storage::disk('public')->download($avaluo->documento_path);
    }
}
