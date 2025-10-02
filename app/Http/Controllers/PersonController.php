<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        $this->authorize('viewAny', Person::class); // ✅ POLICY

        $search   = request('search');
        $paginate = request('paginate', 10);

        // nombre completo para búsqueda
        $fullNameRaw = "TRIM(CONCAT(
            COALESCE(first_name, ''), ' ',
            COALESCE(middle_name, ''), ' ',
            COALESCE(paternal_surname, ''), ' ',
            COALESCE(maternal_surname, '')
        ))";

        $data = Person::query()
            ->select('*')
            ->selectRaw("$fullNameRaw as full_name")
            ->when($search, function ($q) use ($search, $fullNameRaw) {
                if (is_numeric($search)) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('id', $search)
                            ->orWhere('ci', 'like', "%{$search}%")
                            ->orWhere('nit', 'like', "%{$search}%");
                    });
                }
                $q->orWhere(function ($sub) use ($search, $fullNameRaw) {
                    $sub->where('phone', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('paternal_surname', 'like', "%{$search}%")
                        ->orWhere('maternal_surname', 'like', "%{$search}%")
                        ->orWhere('legal_name', 'like', "%{$search}%")
                        ->orWhereRaw("{$fullNameRaw} like ?", ["%{$search}%"]);
                });
            })
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

    public function store(Request $request)
    {
        $this->authorize('create', Person::class); // ✅ POLICY

        \Log::info('INPUT RECIBIDO', $request->all());

        try {
            // Validación
            $request->validate($this->rules());

            // Datos
            $data = $request->except('image');
            $data['image'] = $request->hasFile('image') ? $this->storeImage($request->file('image')) : null;

            \Log::info('DATOS A INSERTAR', $data);

            // Crear
            $person = Person::create($data);

            \Log::info('PERSONA CREADA', ['id' => $person->id]);

            return redirect()->route('admin.people.index')
                ->with(['message' => 'Persona creada.', 'alert-type' => 'success']);

        } catch (\Throwable $e) {
            \Log::error('ERROR EN STORE', ['msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withInput()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }
    }

    /* ----------  EDICIÓN  ---------- */
    public function edit(Person $person)
    {
        $this->authorize('update', $person); // ✅ POLICY
        return view('admin.people.edit-add', compact('person'));
    }

    public function update(Request $request, Person $person)
    {
        $this->authorize('update', $person); // ✅ POLICY

        $request->validate($this->rules($person->id));

        DB::beginTransaction();
        try {
            $data = $request->except('image');
            if ($request->hasFile('image')) {
                $data['image'] = $this->storeImage($request->file('image'), $person->image);
            }
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
        $this->authorize('delete', $person); // ✅ POLICY
        $person->delete();
        return redirect()->route('admin.people.index')
            ->with(['message' => 'Persona eliminada.', 'alert-type' => 'success']);
    }

    /* ----------  REGLAS DE VALIDACIÓN  ---------- */
    private function rules($id = null)
    {
        $uniqueCi   = $id ? "unique:people,ci,$id"          : 'unique:people';
        $uniqueNit  = $id ? "unique:people,nit,$id"         : 'unique:people';

        return [
            'person_type'         => 'required|in:Natural,Jurídica',
            'tipo_doc'            => 'required|in:CI,NIT,PASS',
            'ci'                  => 'nullable|max:20|'.$uniqueCi,
            'ci_complemento'      => 'nullable|max:5',
            'nit'                 => 'nullable|max:20|'.$uniqueNit,
            'legal_name'          => 'nullable|max:100',
            'first_name'          => 'nullable|max:50',
            'middle_name'         => 'nullable|max:50',
            'paternal_surname'    => 'nullable|max:50',
            'maternal_surname'    => 'nullable|max:50',
            'birth_date'          => 'nullable|date',
            'email'               => 'nullable|email|max:100',
            'phone'               => 'nullable|max:50',
            'address'             => 'nullable|max:255',
            'gender'              => 'nullable|in:Masculino,Femenino',
            'image'               => 'nullable|image|max:2048',
            'status'              => 'nullable|in:0,1,2',
            'estado_persona'      => 'nullable|in:Activo,Inactivo,Fallecido',
        ];
    }

    /* ----------  GUARDAR IMAGEN  ---------- */
    private function storeImage($file, $old = null)
    {
        if ($old) {
            Storage::disk('public')->delete($old);
        }
        return $file ? $file->store('people', 'public') : null;
    }
}
