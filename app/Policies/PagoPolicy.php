<?php

// app/Policies/PagoPolicy.php
namespace App\Policies;

use App\Models\Pago;
use App\Models\User;

class PagoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('browse_pagos');
    }

    public function view(User $user, Pago $pago): bool
    {
        return $user->hasPermission('read_pagos');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('add_pagos');
    }

    public function delete(User $user, Pago $pago): bool
    {
        return $user->hasPermission('delete_pagos');
    }

    public function before(User $user, $ability): ?bool
    {
        return $user->hasPermission('browse_admin') ?: null;
    }
}
