<?php

namespace App\Policies;

use App\Models\Jurisdiccion;
use App\Models\User;

class JurisdiccionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function view(User $user, Jurisdiccion $jurisdiccion): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function create(User $user): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function update(User $user, Jurisdiccion $jurisdiccion): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function delete(User $user, Jurisdiccion $jurisdiccion): bool
    {
        return $user->can('core_institucional.administrar');
    }
}
