<?php

// app/Policies/AdquirenteTramitePolicy.php
namespace App\Policies;

use App\Models\AdquirenteTramite;
use App\Models\User;

class AdquirenteTramitePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('browse_adquirentes_tramite');
    }

    public function view(User $user, AdquirenteTramite $item): bool
    {
        return $user->hasPermission('read_adquirentes_tramite');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('add_adquirentes_tramite');
    }

    public function delete(User $user, AdquirenteTramite $item): bool
    {
        return $user->hasPermission('delete_adquirentes_tramite');
    }

    public function before(User $user, $ability): ?bool
    {
        return $user->hasPermission('browse_admin') ?: null;
    }
}
