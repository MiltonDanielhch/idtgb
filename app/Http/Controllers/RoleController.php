<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use TCG\Voyager\Models\Role;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // public function index()
    // {
    //     $this->custom_authorize('browse_roles');
    //     return view('administrations.people.browse');
    // }

    public function list(){

        $search = request('search') ?? null;
        $paginate = request('paginate') ?? 10;

        $rol_id = Auth::user()->role->id;

        $data = Role::where(function($query) use ($search){
                            $query->OrWhereRaw($search ? "id = '$search'" : 1)
                            ->OrWhereRaw($search ? "name like '%$search%'" : 1)
                            ->OrWhereRaw($search ? "display_name like '%$search%'" : 1);
                        })
                        // ->where('deleted_at', NULL)
                        ->whereRaw($rol_id!=1? 'id != 1':1)
                        ->orderBy('id', 'DESC')->paginate($paginate);

        return view('vendor.voyager.roles.list', compact('data'));
    }
}
