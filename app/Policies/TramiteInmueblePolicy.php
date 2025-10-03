<?php

namespace App\Policies;

use App\Models\TramiteInmueble;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TramiteInmueblePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('browse_tramite_inmuebles');
    }

    public function view(User $user, TramiteInmueble $tramiteInmueble): bool
    {
        return $user->hasPermission('read_tramite_inmuebles');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('add_tramite_inmuebles');
    }

    public function update(User $user, TramiteInmueble $tramiteInmueble): bool
    {
        return $user->hasPermission('edit_tramite_inmuebles');
    }

    public function delete(User $user, TramiteInmueble $tramiteInmueble): bool
    {
        return $user->hasPermission('delete_tramite_inmuebles');
    }

    public function before(User $user, $ability): ?bool
    {
        return $user->hasPermission('browse_admin') ?: null;
    }
}
