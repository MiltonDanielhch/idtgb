<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\Http\Request;
use App\Http\Requests\StorePersonRequest;
use App\Http\Requests\UpdatePersonRequest;
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

    public function store(StorePersonRequest $request)
    {
        $this->authorize('create', Person::class);

        try {
            $data = $request->except('image');
            $data['image'] = $request->hasFile('image') ? $this->storeImage($request->file('image')) : null;

            Person::create($data);

            return redirect()->route('admin.people.index')
                ->with(['message' => 'Persona creada.', 'alert-type' => 'success']);

        } catch (\Throwable $e) {
            return back()->withInput()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }
    }
    /* ----------  EDICIÓN  ---------- */
    public function edit(Person $person)
    {
        $this->authorize('update', $person); // ✅ POLICY
        return view('admin.people.edit-add', compact('person'));
    }

    public function update(UpdatePersonRequest $request, Person $person)
    {
        $this->authorize('update', $person);

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

    /* ----------  GUARDAR IMAGEN  ---------- */
    private function storeImage($file, $old = null)
    {
        if ($old) {
            Storage::disk('public')->delete($old);
        }
        return $file ? $file->store('people', 'public') : null;
    }

    public function datatable(Request $request)
    {
        $this->authorize('viewAny', Person::class);

        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length"); // Rows display per page

        if ($start < 0) $start = 0;
        if ($rowperpage < 1) $rowperpage = 10;

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');

        // $columnIndex = $columnIndex_arr[0]['column']; // Column index
        // $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        // $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        // $searchValue = $search_arr['value']; // Search value

        // Seguridad para evitar null
        $columnIndex = isset($columnIndex_arr[0]['column']) ? (int) $columnIndex_arr[0]['column'] : 0;
        $columnName = isset($columnName_arr[$columnIndex]['data']) ? $columnName_arr[$columnIndex]['data'] : 'id';
        $columnSortOrder = isset($order_arr[0]['dir']) && in_array(strtolower($order_arr[0]['dir']), ['asc', 'desc']) ? $order_arr[0]['dir'] : 'asc';
        $searchValue = isset($search_arr['value']) ? trim($search_arr['value']) : '';

        // Total records
        $totalRecords = Person::count();
        $query = Person::query();
        $query->where(function ($q) use ($searchValue) {
            $q->where('first_name', 'like', '%' . $searchValue . '%')
                  ->orWhere('paternal_surname', 'like', '%' . $searchValue . '%')
                  ->orWhere('maternal_surname', 'like', '%' . $searchValue . '%')
                  ->orWhere('ci', 'like', '%' . $searchValue . '%');
        });
        $totalRecordswithFilter = $query->count();

        // Fetch records
        $records = $query->select('people.*')
            ->orderBy($columnName, $columnSortOrder)
            ->skip($start)
            ->take($rowperpage)
            ->get();

        $data_arr = array();

        foreach ($records as $record) {
            $data_arr[] = array(
                "id" => $record->id,
                "full_name" => $record->full_name, // Assuming full_name is an accessor
                "ci" => $record->ci,
                "action" => '' // Action column will be populated by JS in the view
            );
        }

        $response = array(
            "draw" => intval($draw),
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $totalRecordswithFilter,
            "data" => $data_arr,
        );

        return response()->json($response);
    }
}
