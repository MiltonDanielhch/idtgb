<?php

namespace App\Policies;

use App\Models\TipoTransmision;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TipoTransmisionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('browse_tipos-transmision');
    }

    public function view(User $user, TipoTransmision $tipoTransmision): bool
    {
        return $user->hasPermission('read_tipos-transmision');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('add_tipos-transmision');
    }

    public function update(User $user, TipoTransmision $tipoTransmision): bool
    {
        return $user->hasPermission('edit_tipos-transmision');
    }

    public function delete(User $user, TipoTransmision $tipoTransmision): bool
    {
        return $user->hasPermission('delete_tipos-transmision');
    }

    public function restore(User $user, TipoTransmision $tipoTransmision): bool
    {
        return $user->hasPermission('browse_admin');
    }

    public function forceDelete(User $user, TipoTransmision $tipoTransmision): bool
    {
        return $user->hasPermission('browse_admin');
    }

    public function before(User $user, $ability)
    {
        if ($user->hasPermission('browse_admin')) {
            return true;
        }
        return null;
    }
}
