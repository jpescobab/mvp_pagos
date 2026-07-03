<?php

namespace App\Policies;

use App\Models\User;

class CfinancieroPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('core_institucional.administrar');
    }
}
