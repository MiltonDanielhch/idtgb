<?php

namespace App\Policies;

use App\Models\Exencion;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExencionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->hasPermission('browse_exenciones');
    }

    public function view(User $user, Exencion $exencion)
    {
        return $user->hasPermission('read_exenciones');
    }

    public function create(User $user)
    {
        return $user->hasPermission('add_exenciones');
    }

    public function update(User $user, Exencion $exencion)
    {
        return $user->hasPermission('edit_exenciones');
    }

    public function delete(User $user, Exencion $exencion)
    {
        return $user->hasPermission('delete_exenciones');
    }
}
