<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Person;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // public function index()
    // {
    //     $this->custom_authorize('browse_users');
    //     return User::all();

    //     return view('vendor.voyager.users.broswse');
    // }


    public function list()
    {
        // $this->custom_authorize('browse_users');
        $rol_id = Auth::user()->role->id;

        $search = request('search') ?? null;
        $paginate = request('paginate') ?? 10;

        $data = User::with(['person'])
                    ->where(function($query) use ($search){
                        $query->OrWhereRaw($search ? "id = '$search'" : 1)
                        ->OrWhereRaw($search ? "name like '%$search%'" : 1)
                        ->OrWhereRaw($search ? "email like '%$search%'" : 1);
                    })
                    // ->where('deleted_at', NULL)
                    ->whereRaw($rol_id!=1? 'role_id != 1':1)
                    ->orderBy('id', 'DESC')
                    ->paginate($paginate);
        return view('vendor.voyager.users.list', compact('data'));
    }


    public function store(Request $request)
    {
        $data = User::where('email', $request->email)->first();
        if($data)
        {
            return redirect()->route('voyager.users.index')->with(['message' => 'El correo ya existe.', 'alert-type' => 'warning    ']);
        }
        $person = Person::where('deleted_at', null)->where('id', $request->person_id)->first();

        DB::beginTransaction();
        try {

            User::create([
                'person_id' => $request->person_id,
                'name' =>  $person->nombre_completo,
                'role_id' => $request->role_id,
                'email' => $request->email,
                'avatar' => 'users/default.png',
                'password' => bcrypt($request->password),
                // 'settings' => '{"locale":"es"}'

            ]);
            DB::commit();
            return redirect()->route('voyager.users.index')->with(['message' => 'Registrado exitosamente.', 'alert-type' => 'success']);

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->route('voyager.users.index')->with(['message' => 'Ocurrió un error.', 'alert-type' => 'error']);
        }

    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = User::where('id', $id)->first();
            $user->update([
                'status'=> $request->status?1:0,
            ]);

            if($request->role_id)
            {
                $user->update([
                    'role_id' => $request->role_id,
                ]);
            }
            if($request->password)
            {
                $user->update([
                    'password' => bcrypt($request->password)
                ]);
            }
            DB::commit();
            return redirect()->route('voyager.users.index')->with(['message' => 'Actualizado exitosamente.', 'alert-type' => 'success']);

        } catch (\Exception $e) {
            DB::rollback();

            return redirect()->route('voyager.users.index')->with(['message' => 'Ocurrió un error.', 'alert-type' => 'error']);
        }
    }

    public function destroy(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = User::where('id', $id)->where('deleted_at', null)->first();
            $user->delete();
            DB::commit();
            return redirect()->route('voyager.users.index')->with(['message' => 'Eliminado exitosamente.', 'alert-type' => 'success']);
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->route('voyager.users.index')->with(['message' => 'Ocurrió un error.', 'alert-type' => 'error']);
        }
    }
}
