<?php

namespace App\Policies;

use App\Models\TipoInmueble;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TipoInmueblePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('browse_tipos-inmueble');
    }

    public function view(User $user, TipoInmueble $tipoInmueble): bool
    {
        return $user->hasPermission('read_tipos-inmueble');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('add_tipos-inmueble');
    }

    public function update(User $user, TipoInmueble $tipoInmueble): bool
    {
        return $user->hasPermission('edit_tipos-inmueble');
    }

    public function delete(User $user, TipoInmueble $tipoInmueble): bool
    {
        return $user->hasPermission('delete_tipos-inmueble');
    }

    public function before(User $user, $ability)
    {
        if ($user->hasPermission('browse_admin')) {
            return true;
        }
        return null;
    }
}
