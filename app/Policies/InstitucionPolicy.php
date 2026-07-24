<?php

namespace App\Policies;

use App\Models\Institucion;
use App\Models\User;

class InstitucionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function view(User $user, Institucion $institucion): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function create(User $user): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function update(User $user, Institucion $institucion): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function delete(User $user, Institucion $institucion): bool
    {
        return $user->can('core_institucional.administrar');
    }
}
