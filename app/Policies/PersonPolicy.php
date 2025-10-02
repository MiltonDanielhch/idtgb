<?php

namespace App\Policies;

use App\Models\Person;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PersonPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->hasPermission('browse_people');
    }

    public function view(User $user, Person $person)
    {
        return $user->hasPermission('read_people');
    }

    public function create(User $user)
    {
        return $user->hasPermission('add_people');
    }

    public function update(User $user, Person $person)
    {
        return $user->hasPermission('edit_people');
    }

    public function delete(User $user, Person $person)
    {
        return $user->hasPermission('delete_people');
    }

    public function before(User $user, $ability)
    {
        if ($user->hasPermission('browse_admin')) {
            return true;
        }
        return null;
    }
}
