<?php

namespace App\Policies;

use App\Models\User;

class CcostoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('core_institucional.administrar');
    }
}
