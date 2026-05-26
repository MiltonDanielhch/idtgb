<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\Http\Request;
use App\Http\Requests\StorePersonRequest;
use App\Http\Requests\UpdatePersonRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PersonController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* ----------  LISTADO (AJAX)  ---------- */
    public function index()
    {
        $this->authorize('viewAny', Person::class); // ✅ POLICY
        return view('admin.people.browse');
    }

    /* ----------  LECTURA  ---------- */
    public function show(Person $person)
    {
        $this->authorize('view', $person); // ✅ POLICY
        return view('admin.people.read', compact('person'));
    }

    public function list()
    {
        $this->authorize('viewAny', Person::class);

        $search   = request('search');
        $paginate = request('paginate', 10);

        $data = Person::query()
            ->search($search)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->paginate($paginate);

        return view('admin.people.list', compact('data'));
    }

    /* ----------  ALTA  ---------- */
    public function create()
    {
        $this->authorize('create', Person::class); // ✅ POLICY
        return view('admin.people.edit-add', ['person' => new Person()]);
    }

    public function store(StorePersonRequest $request)
    {
        $this->authorize('create', Person::class);

        try {
            $data = $request->validated();
            Person::create($data);

            return redirect()->route('admin.people.index')
                ->with(['message' => 'Persona creada.', 'alert-type' => 'success']);

        } catch (\Throwable $e) {
            \Log::error('Error al crear persona: ' . $e->getMessage());
            return back()->withInput()->with(['message' => 'Ocurrió un error inesperado al guardar la persona.', 'alert-type' => 'error']);
        }
    }
    /* ----------  EDICIÓN  ---------- */
    public function edit(Person $person)
    {
        $this->authorize('update', $person); // ✅ POLICY
        return view('admin.people.edit-add', ['person' => $person]);
    }

    public function update(UpdatePersonRequest $request, Person $person)
    {
        $this->authorize('update', $person);

        DB::beginTransaction();
        try {
            $data = $request->validated();
            $person->update($data);
            DB::commit();

            return redirect()->route('admin.people.index')
                ->with(['message' => 'Persona actualizada.', 'alert-type' => 'success']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }
    }

    /* ----------  ELIMINAR  ---------- */
    public function destroy(Person $person)
    {
        $this->authorize('delete', $person); 

        if ($person->adquirentesTramite()->exists() || $person->disponentesTramite()->exists()) {
            return redirect()->route('admin.people.index')
                ->with(['message' => 'No se puede eliminar: la persona está asociada a uno o más trámites.', 'alert-type' => 'error']);
        }

        $person->delete();

        return redirect()->route('admin.people.index')
            ->with(['message' => 'Persona eliminada.', 'alert-type' => 'success']);
    }

}
