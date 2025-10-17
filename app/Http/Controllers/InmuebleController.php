<?php

namespace App\Http\Controllers;

use App\Models\Inmueble;
use App\Models\TipoInmueble;
use App\Models\Municipio;
use App\Http\Requests\StoreInmuebleRequest;
use App\Http\Requests\UpdateInmuebleRequest;
use Illuminate\Support\Facades\DB;
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
            ->orderBy('catastro')
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
            'municipios'  => Municipio::with('provincia.departamento')->orderBy('nombre')->get(),
        ]);
    }

    public function store(StoreInmuebleRequest $request)
    {
        $this->authorize('create', Inmueble::class);
        Inmueble::create($request->validated());
        return redirect()->route('admin.inmuebles.index')
            ->with(['message' => 'Inmueble creado.', 'alert-type' => 'success']);
    }

    /* ----------  EDICIÓN  ---------- */
    public function edit(Inmueble $inmueble)
    {
        $this->authorize('update', $inmueble);
        return view('admin.inmuebles.edit-add', [
            'inmueble'    => $inmueble,
            'tipos'       => TipoInmueble::orderBy('nombre')->get(),
            'municipios'  => Municipio::with('provincia.departamento')->orderBy('nombre')->get(),
        ]);
    }

    public function update(UpdateInmuebleRequest $request, Inmueble $inmueble)
    {
        $this->authorize('update', $inmueble);
        $inmueble->update($request->validated());
        return redirect()->route('admin.inmuebles.index')
            ->with(['message' => 'Inmueble actualizado.', 'alert-type' => 'success']);
    }

    public function datatable(Request $request)
    {
        $this->authorize('viewAny', Inmueble::class);

        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length"); // Rows display per page

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');

        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        $searchValue = $search_arr['value']; // Search value

        // Total records
        $totalRecords = Inmueble::count();
        
        $query = Inmueble::query();
        if ($searchValue) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('numero_matricula', 'like', '%' . $searchValue . '%')
                      ->orWhere('direccion', 'like', '%' . $searchValue . '%')
                      ->orWhere('superficie_terreno', 'like', '%' . $searchValue . '%');
            });
        }
        $totalRecordswithFilter = $query->count();

        // Fetch records
        $records = $query->select('inmuebles.*')
            ->orderBy($columnName, $columnSortOrder)
            ->skip($start)
            ->take($rowperpage)
            ->get();

        $data_arr = array();
        
        foreach ($records as $record) {
            $data_arr[] = array(
                "id" => $record->id,
                "numero_matricula" => $record->numero_matricula,
                "direccion" => $record->direccion,
                "superficie_terreno" => $record->superficie_terreno,
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
}