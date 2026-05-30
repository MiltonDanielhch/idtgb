<?php

namespace App\Http\Controllers;

use App\Models\Feriado;
use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FeriadoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* ----------  LISTADO  ---------- */
    public function index()
    {
        return view('admin.feriados.browse');
    }

    public function list(Request $request)
    {
        $search = $request->get('search', '');
        $paginate = $request->get('paginate', 10);
        $tipo = $request->get('tipo', '');
        $departamento = $request->get('departamento', '');

        $query = Feriado::with(['departamento']);

        if ($search) {
            $query->where('nombre', 'like', '%' . $search . '%')
                  ->orWhere('fecha', 'like', '%' . $search . '%');
        }

        if ($tipo) {
            $query->where('tipo', $tipo);
        }

        if ($departamento) {
            $query->where('departamento_id', $departamento);
        }

        $feriados = $query->orderBy('fecha', 'desc')->paginate($paginate);

        return view('admin.feriados.list', compact('feriados'));
    }

    public function show(Feriado $feriado)
    {
        $feriado->load(['departamento', 'createdBy', 'updatedBy']);
        return view('admin.feriados.read', compact('feriado'));
    }

    /* ----------  ALTA  ---------- */
    public function create()
    {
        $departamentos = Departamento::all();
        return view('admin.feriados.edit_add', [
            'feriado' => new Feriado(),
            'departamentos' => $departamentos,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date|unique:feriados,fecha,NULL,id,departamento_id,' . ($request->departamento_id ?: 'NULL'),
            'nombre' => 'required|string|max:100',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'tipo' => 'required|in:Nacional,Departamental,Municipal',
            'activo' => 'boolean',
        ], [
            'fecha.unique' => 'Ya existe un feriado en esta fecha para este departamento.',
        ]);

        Feriado::create($request->all());

        return redirect()->route('admin.feriados.index')
            ->with(['message' => 'Feriado creado.', 'alert-type' => 'success']);
    }

    /* ----------  EDICIÓN  ---------- */
    public function edit(Feriado $feriado)
    {
        $departamentos = Departamento::all();
        return view('admin.feriados.edit_add', compact('feriado', 'departamentos'));
    }

    public function update(Request $request, Feriado $feriado)
    {
        $request->validate([
            'fecha' => 'required|date|unique:feriados,fecha,' . $feriado->id . ',id,departamento_id,' . ($request->departamento_id ?: 'NULL'),
            'nombre' => 'required|string|max:100',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'tipo' => 'required|in:Nacional,Departamental,Municipal',
            'activo' => 'boolean',
        ], [
            'fecha.unique' => 'Ya existe un feriado en esta fecha para este departamento.',
        ]);

        $feriado->update($request->all());

        return redirect()->route('admin.feriados.index')
            ->with(['message' => 'Feriado actualizado.', 'alert-type' => 'success']);
    }

    /* ----------  BORRADO  ---------- */
    public function destroy(Feriado $feriado)
    {
        try {
            $feriado->delete();
            return redirect()->route('admin.feriados.index')
                ->with(['message' => 'Feriado eliminado.', 'alert-type' => 'success']);
        } catch (\Exception $e) {
            Log::error("Error al eliminar Feriado #{$feriado->id}: " . $e->getMessage());
            return redirect()->route('admin.feriados.index')
                ->with(['message' => 'Ocurrió un error al intentar eliminar el feriado.', 'alert-type' => 'error']);
        }
    }

    /* ----------  IMPORTAR FERIADOS NACIONALES  ---------- */
    public function importarNacionales()
    {
        // Esta función podría importar feriados desde una API externa
        // Por ahora, solo muestra la vista
        return view('admin.feriados.importar');
    }
}
