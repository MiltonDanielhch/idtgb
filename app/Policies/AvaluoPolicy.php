<?php

namespace App\Policies;

use App\Models\Avaluo;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AvaluoPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user){
        return $user->hasPermission('browse_avaluos');
    }

    public function view(User $user, Avaluo $avaluo){
        return $user->hasPermission('read_avaluos');
    }

    public function create(User $user){
        return $user->hasPermission('add_avaluos');
    }

    public function update(User $user, Avaluo $avaluo){
        return $user->hasPermission('edit_avaluos');
    }

    public function delete(User $user, Avaluo $avaluo){
        return $user->hasPermission('delete_avaluos');
    }

    public function before(User $user, $ability)
    {
        return $user->hasPermission('browse_admin') ?: null;
    }
}
