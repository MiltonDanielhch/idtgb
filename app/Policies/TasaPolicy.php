<?php

namespace App\Policies;

use App\Models\Tasa;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TasaPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user){
        return $user->hasPermission('browse_tasas');
    }

    public function view(User $user, Tasa $tasa){
        return $user->hasPermission('read_tasas');
    }

    public function create(User $user){
        return $user->hasPermission('add_tasas');
    }

    public function update(User $user, Tasa $tasa){
        return $user->hasPermission('edit_tasas');
    }

    public function delete(User $user, Tasa $tasa){
        return $user->hasPermission('delete_tasas');
    }

    public function before(User $user, $ability)
    {
        return $user->hasPermission('browse_admin') ?: null;
    }
}
