<?php

namespace App\Policies;

use App\Models\Inmueble;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InmueblePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)     {
        return $user->hasPermission('browse_inmuebles');
    }

    public function view(User $user, Inmueble $inmueble){
        return $user->hasPermission('read_inmuebles');
    }

    public function create(User $user){
        return $user->hasPermission('add_inmuebles');
    }

    public function update(User $user, Inmueble $inmueble){
        return $user->hasPermission('edit_inmuebles');
    }

    public function delete(User $user, Inmueble $inmueble){
        return $user->hasPermission('delete_inmuebles');
    }

    public function before(User $user, $ability)
    {
        return $user->hasPermission('browse_admin') ?: null;
    }
}
