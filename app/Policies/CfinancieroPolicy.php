<?php

namespace App\Policies;

use App\Models\Cfinanciero;
use App\Models\User;

class CfinancieroPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function view(User $user, Cfinanciero $cfinanciero): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function create(User $user): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function update(User $user, Cfinanciero $cfinanciero): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function delete(User $user, Cfinanciero $cfinanciero): bool
    {
        return $user->can('core_institucional.administrar');
    }
}
