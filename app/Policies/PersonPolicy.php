<?php

namespace App\Policies;

use App\Models\Person;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class PersonPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        Log::info('=== PERSON POLICY viewAny CALLED ===');
        Log::info('User ID: ' . $user->id);
        Log::info('User Role ID: ' . $user->role_id);

        $hasPermission = $user->hasPermission('browse_people');
        Log::info('Has browse_people permission: ' . ($hasPermission ? 'YES' : 'NO'));

        // Debug adicional: ver todos los permisos del usuario
        $userPermissions = $user->role->permissions->pluck('key')->toArray();
        Log::info('All user permissions: ', $userPermissions);

        return $hasPermission;
    }

    public function view(User $user, Person $person)
    {
        Log::info('=== PERSON POLICY view CALLED ===');
        Log::info('User ID: ' . $user->id);
        Log::info('Person ID: ' . $person->id);

        $hasPermission = $user->hasPermission('read_people');
        Log::info('Has read_people permission: ' . ($hasPermission ? 'YES' : 'NO'));

        return $hasPermission;
    }

    public function create(User $user)
    {
        Log::info('=== PERSON POLICY create CALLED ===');
        Log::info('User ID: ' . $user->id);

        $hasPermission = $user->hasPermission('add_people');
        Log::info('Has add_people permission: ' . ($hasPermission ? 'YES' : 'NO'));

        return $hasPermission;
    }

    public function update(User $user, Person $person)
    {
        Log::info('=== PERSON POLICY update CALLED ===');
        Log::info('User ID: ' . $user->id);
        Log::info('Person ID: ' . $person->id);

        $hasPermission = $user->hasPermission('edit_people');
        Log::info('Has edit_people permission: ' . ($hasPermission ? 'YES' : 'NO'));

        return $hasPermission;
    }

    public function delete(User $user, Person $person)
    {
        Log::info('=== PERSON POLICY delete CALLED ===');
        Log::info('User ID: ' . $user->id);
        Log::info('Person ID: ' . $person->id);

        $hasPermission = $user->hasPermission('delete_people');
        Log::info('Has delete_people permission: ' . ($hasPermission ? 'YES' : 'NO'));

        return $hasPermission;
    }

    // Método adicional para debug profundo
    public function before(User $user, $ability)
    {
        Log::info("=== PERSON POLICY before() called ===");
        Log::info("Ability: " . $ability);
        Log::info("User ID: " . $user->id);

        // Si el usuario es super admin, permitir todo
        if ($user->hasPermission('browse_admin')) {
            Log::info('User has browse_admin - allowing all');
            return true;
        }

        return null;
    }
}
