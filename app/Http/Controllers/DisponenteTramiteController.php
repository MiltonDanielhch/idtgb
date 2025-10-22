<?php
// app/Http/Controllers/DisponenteTramiteController.php
namespace App\Http\Controllers;

use App\Models\Tramite;
use App\Models\Person;
use App\Models\DisponenteTramite;
use App\Http\Requests\StoreDisponenteTramiteRequest;
use Illuminate\Support\Facades\DB;

class DisponenteTramiteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* ---------- LISTADO (AJAX) ---------- */
    public function index(Tramite $tramite)
    {
        $this->authorize('viewAny', DisponenteTramite::class);
        return view('admin.tramites.disponentes.browse', compact('tramite'));
    }

    public function list(Tramite $tramite)
    {
        $this->authorize('viewAny', DisponenteTramite::class);

        $search   = request('search');
        $paginate = request('paginate', 10);

        $data = DisponenteTramite::with(['person'])
            ->where('tramite_id', $tramite->id)
            ->when($search, fn($q) => $q->whereHas('person', fn($sq) => $sq->where('ci', 'like', "%{$search}%")->orWhere('first_name', 'like', "%{$search}%")->orWhere('paternal_surname', 'like', "%{$search}%")))
            ->orderBy('id')
            ->paginate($paginate);

        return view('admin.tramites.disponentes.list', compact('tramite', 'data'));
    }

    /* ---------- LECTURA ---------- */
    public function show(Tramite $tramite, DisponenteTramite $item)
    {
        $this->authorize('view', $item);
        return view('admin.tramites.disponentes.read', compact('tramite', 'item'));
    }

    /* ---------- ALTA ---------- */
    public function create(Tramite $tramite)
    {
        $this->authorize('create', DisponenteTramite::class);

        $personas = Person::where('status', 1)
            ->where('estado_persona', 'Activo')
            ->whereDoesntHave('disponentesTramite', fn($q) => $q->where('tramite_id', $tramite->id))
            ->orderBy('first_name')
            ->orderBy('paternal_surname')
            ->get();

        $tipos = ['Causante', 'Donante', 'Testador'];

        // Es necesario crear una instancia vacía para que la vista 'edit-add'
        // funcione correctamente en modo 'creación'.
        $item = new DisponenteTramite();

        return view('admin.tramites.disponentes.edit-add', compact('tramite', 'personas', 'tipos', 'item'));
    }

    public function store(StoreDisponenteTramiteRequest $request, Tramite $tramite)
    {
        $this->authorize('create', DisponenteTramite::class);

        DB::beginTransaction();
        try {
            DisponenteTramite::create([
                'tramite_id' => $tramite->id,
                'person_id' => $request->person_id,
                'tipo' => $request->tipo,
                'fecha_fallecimiento' => $request->fecha_fallecimiento,
                'es_discapacitado' => $request->boolean('es_discapacitado'),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('admin.tramites.disponentes.index', $tramite)
                ->with(['message' => 'Disponente agregado.', 'alert-type' => 'success']);

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }
    }

    /* ---------- BORRADO ---------- */
    public function destroy(Tramite $tramite, DisponenteTramite $item)
    {
        $this->authorize('delete', $item);

        if ($item->tramite_id !== $tramite->id) {
            abort(404);
        }

        DB::beginTransaction();
        try {
            $item->delete();
            DB::commit();

            return redirect()->route('admin.tramites.disponentes.index', $tramite)
                ->with(['message' => 'Disponente quitado.', 'alert-type' => 'success']);

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }
    }
}
