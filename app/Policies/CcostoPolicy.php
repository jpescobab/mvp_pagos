<?php

namespace App\Policies;

use App\Models\Ccosto;
use App\Models\User;

class CcostoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function view(User $user, Ccosto $ccosto): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function create(User $user): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function update(User $user, Ccosto $ccosto): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function delete(User $user, Ccosto $ccosto): bool
    {
        return $user->can('core_institucional.administrar');
    }
}
