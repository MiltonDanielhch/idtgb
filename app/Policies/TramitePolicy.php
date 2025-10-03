<?php

namespace App\Policies;

use App\Models\Tramite;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TramitePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user){
        return $user->hasPermission('browse_tramites');
    }

    public function view(User $user, Tramite $tramite){
        return $user->hasPermission('read_tramites');
    }

    public function create(User $user){
        return $user->hasPermission('add_tramites');
    }

    public function update(User $user, Tramite $tramite){
        return $user->hasPermission('edit_tramites');
    }

    public function delete(User $user, Tramite $tramite){
        return $user->hasPermission('delete_tramites');
    }

    public function before(User $user, $ability)
    {
        return $user->hasPermission('browse_admin') ?: null;
    }
}
