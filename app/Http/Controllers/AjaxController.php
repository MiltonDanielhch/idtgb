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
                        ->OrWhereRaw($q ? "first_name like '%$q%'" : 1)
                        ->OrWhereRaw($q ? "middle_name like '%$q%'" : 1)
                        ->OrWhereRaw($q ? "paternal_surname like '%$q%'" : 1)
                        ->OrWhereRaw($q ? "maternal_surname like '%$q%'" : 1)
                        ->orWhere(function ($subQ) use ($q) {
                            $subQ->whereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(middle_name, '')) like ?", ["%$q%"])
                                ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(paternal_surname, ''), ' ', COALESCE(maternal_surname, '')) like ?", ["%$q%"])
                                ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(middle_name, ''), ' ', COALESCE(paternal_surname, ''), ' ', COALESCE(maternal_surname, '')) like ?", ["%$q%"]);
                        })
                        ->where('deleted_at', null)
                        ->get();
        return response()->json(['results' => $data]);
    }

    public function personStore(Request $request){
        DB::beginTransaction();
        try {
            $person =Person::create($request->all());
            DB::commit();
            return response()->json(['person' => $person]);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
}
