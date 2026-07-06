<?php

namespace App\Policies;

use App\Models\Asignacion;
use App\Models\User;

class AsignacionPolicy
{
    public function create(User $user): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function update(User $user, Asignacion $asignacion): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function delete(User $user, Asignacion $asignacion): bool
    {
        return $user->can('core_institucional.administrar');
    }
}
