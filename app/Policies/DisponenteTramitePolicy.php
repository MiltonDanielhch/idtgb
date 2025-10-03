<?php

// app/Policies/DisponenteTramitePolicy.php
namespace App\Policies;

use App\Models\DisponenteTramite;
use App\Models\User;

class DisponenteTramitePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('browse_disponentes_tramite');
    }

    public function view(User $user, DisponenteTramite $item): bool
    {
        return $user->hasPermission('read_disponentes_tramite');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('add_disponentes_tramite');
    }

    public function delete(User $user, DisponenteTramite $item): bool
    {
        return $user->hasPermission('delete_disponentes_tramite');
    }

    public function before(User $user, $ability): ?bool
    {
        return $user->hasPermission('browse_admin') ?: null;
    }
}
