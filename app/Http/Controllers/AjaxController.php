<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Person;
use Illuminate\Support\Facades\DB;

class AjaxController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
    }

    public function personList(){
        $q = request('q');
        $data = Person::OrWhereRaw($q ? "ci like '%$q%'" : 1)
                        ->OrWhereRaw($q ? "phone like '%$q%'" : 1)
                        ->OrWhereRaw($q ? "nombre_completo like '%$q%'" : 1)
                        ->where('deleted_at', null)
                        ->get();
        return response()->json($data);
    }

    public function personStore(Request $request){
        DB::beginTransaction();
        try {
            // Solo aceptar campos del formulario
            $data = $request->only(['nombre_completo', 'ci', 'phone']);
            
            // Agregar campos por defecto
            $data['person_type'] = 'Natural';
            $data['tipo_doc'] = 'CI';
            
            $person = Person::create($data);
            DB::commit();
            return response()->json([
                'success' => true,
                'person' => $person
            ]);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
