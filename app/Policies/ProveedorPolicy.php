<?php

namespace App\Policies;

use App\Models\User;

class ProveedorPolicy
{
    public function create(User $user): bool
    {
        return $user->can('core_institucional.administrar');
    }
}
