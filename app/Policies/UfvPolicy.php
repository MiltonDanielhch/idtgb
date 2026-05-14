<?php

namespace App\Policies;

use App\Models\Ufv;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;


class UfvPolicy
{
    use HandlesAuthorization;

    // app/Policies/UfvPolicy.php
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('browse_ufvs');
    }
    public function create(User $user): bool
    {
        return $user->hasPermission('add_ufvs');
    }
    public function view(User $user, Ufv $ufv): bool
    {
        return $user->hasPermission('read_ufvs');
    }
    public function update(User $user, Ufv $ufv): bool
    {
        return $user->hasPermission('edit_ufvs');
    }
    public function delete(User $user, Ufv $ufv): bool
    {
        return $user->hasPermission('delete_ufvs');
    }
    public function before(User $user, $ability): ?bool
    {
        return $user->hasPermission('browse_admin') ?: null;
    }

}



