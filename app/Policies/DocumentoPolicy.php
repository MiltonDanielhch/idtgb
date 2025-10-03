<?php

// app/Policies/DocumentoPolicy.php
namespace App\Policies;

use App\Models\Documento;
use App\Models\User;

class DocumentoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('browse_documentos');
    }

    public function view(User $user, Documento $documento): bool
    {
        return $user->hasPermission('read_documentos');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('add_documentos');
    }

    public function delete(User $user, Documento $documento): bool
    {
        return $user->hasPermission('delete_documentos');
    }

    public function before(User $user, $ability): ?bool
    {
        return $user->hasPermission('browse_admin') ?: null;
    }
}
