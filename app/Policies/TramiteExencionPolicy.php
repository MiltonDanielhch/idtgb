<?php

// app/Policies/TramiteExencionPolicy.php
namespace App\Policies;

use App\Models\TramiteExencion;
use App\Models\User;

class TramiteExencionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('browse_tramite_exenciones');
    }

    public function view(User $user, TramiteExencion $item): bool
    {
        return $user->hasPermission('read_tramite_exenciones');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('add_tramite_exenciones');
    }

    public function delete(User $user, TramiteExencion $item): bool
    {
        return $user->hasPermission('delete_tramite_exenciones');
    }

    public function before(User $user, $ability): ?bool
    {
        return $user->hasPermission('browse_admin') ?: null;
    }
}
