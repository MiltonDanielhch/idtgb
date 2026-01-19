<?php

namespace App\Http\Controllers;

use App\Models\Inmueble;
use App\Models\TipoInmueble;
use App\Models\Municipio;
use App\Http\Requests\UpdateInmuebleRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class InmuebleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* ----------  LISTADO (AJAX)  ---------- */
    public function index()
    {
        $this->authorize('viewAny', Inmueble::class);
        return view('admin.inmuebles.browse');
    }

    public function list()
    {
        $this->authorize('viewAny', Inmueble::class);

        $search   = request('search');
        $paginate = request('paginate', 10);

        $data = Inmueble::with(['tipoInmueble', 'municipio.provincia.departamento'])
            ->when($search, fn($q) => $q->where('catastro', 'like', "%{$search}%")
                ->orWhere('direccion', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->paginate($paginate);

        return view('admin.inmuebles.list', compact('data'));
    }

    /* ----------  LECTURA  ---------- */
    public function show(Inmueble $inmueble)
    {
        $this->authorize('view', $inmueble);
        return view('admin.inmuebles.read', compact('inmueble'));
    }

    /* ----------  ALTA  ---------- */
    public function create()
    {
        $this->authorize('create', Inmueble::class);
        return view('admin.inmuebles.edit-add', [
            'inmueble'    => new Inmueble(),
            'tipos'       => TipoInmueble::orderBy('nombre')->get(),
            'municipios'   => Municipio::limit(100)->with('provincia.departamento')->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Inmueble::class);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'catastro' => 'required|unique:inmuebles,catastro',
            'direccion' => 'required',
            'tipo_inmueble_id' => 'required',
            'municipio_id' => 'required',
            'valor_catastral' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with(['message' => 'Error de validación: ' . $validator->errors()->first(), 'alert-type' => 'error']);
        }

        try {
            $data = $validator->validated();
            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();

            Inmueble::create($data);

            return redirect()->route('admin.inmuebles.index')
                ->with(['message' => 'Inmueble creado exitosamente.', 'alert-type' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error al crear inmueble: ' . $e->getMessage());
            return back()->withInput()
                ->with(['message' => 'Ocurrió un error al guardar el inmueble.', 'alert-type' => 'error']);
        }
    }

    /* ----------  EDICIÓN  ---------- */
    public function edit(Inmueble $inmueble)
    {
        $this->authorize('update', $inmueble);
        return view('admin.inmuebles.edit-add', [
            'inmueble'    => $inmueble,
            'tipos'       => TipoInmueble::orderBy('nombre')->get(),
            'municipios'   => Municipio::limit(100)->with('provincia.departamento')->orderBy('nombre')->get(),
        ]);
    }

    public function update(UpdateInmuebleRequest $request, Inmueble $inmueble)
    {
        $this->authorize('update', $inmueble);

        try {
            $validated = $request->validated();

            // Asignar usuario actual
            $validated['updated_by'] = auth()->id();

            $inmueble->update($validated);

            return redirect()->route('admin.inmuebles.index')
                ->with(['message' => 'Inmueble actualizado exitosamente.', 'alert-type' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error al actualizar inmueble: ' . $e->getMessage());
            return back()->withInput()
                ->with(['message' => 'Ocurrió un error al actualizar el inmueble.', 'alert-type' => 'error']);
        }
    }

    /* ----------  BORRADO  ---------- */
    public function destroy(Inmueble $inmueble)
    {
        $this->authorize('delete', $inmueble);
        // Si tiene avalúos, podrías validar antes de eliminar
        if ($inmueble->avaluos()->exists()) {
            return back()->with(['message' => 'No se puede eliminar: tiene avalúos asociados.', 'alert-type' => 'error']);
        }
        $inmueble->delete();
        return redirect()->route('admin.inmuebles.index')
            ->with(['message' => 'Inmueble eliminado.', 'alert-type' => 'success']);
    }

    public function ajaxSearch(Request $request)
    {
        $term = $request->get('q', '');

        $inmuebles = Inmueble::where(function($query) use ($term) {
                $query->where('catastro', 'LIKE', "%{$term}%")
                    ->orWhere('direccion', 'LIKE', "%{$term}%")
                    ->orWhere('matricula_rr', 'LIKE', "%{$term}%");
            })
            ->with('tipoInmueble')
            ->limit(20)
            ->get();

        $formatted = $inmuebles->map(function($inmueble) {
            return [
                'id' => $inmueble->id,
                'text' => $inmueble->catastro . ' - ' . $inmueble->direccion,
                'catastro' => $inmueble->catastro,
                'direccion' => $inmueble->direccion,
                'tipo_inmueble' => $inmueble->tipoInmueble->nombre ?? 'N/A',
                'valor_catastral' => $inmueble->valor_catastral
            ];
        });

        return response()->json(['results' => $formatted]);
    }
}
