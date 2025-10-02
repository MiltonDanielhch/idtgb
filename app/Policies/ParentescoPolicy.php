<?php

namespace App\Policies;

use App\Models\Parentesco;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ParentescoPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->hasPermission('browse_parentescos');
    }

    public function view(User $user, Parentesco $parentesco)
    {
        return $user->hasPermission('read_parentescos');
    }

    public function create(User $user)
    {
        return $user->hasPermission('add_parentescos');
    }

    public function update(User $user, Parentesco $parentesco)
    {
        return $user->hasPermission('edit_parentescos');
    }

    public function delete(User $user, Parentesco $parentesco)
    {
        return $user->hasPermission('delete_parentescos');
    }
}
