<?php

namespace App\Policies;

use App\Models\Catalogo;
use App\Models\User;

class CatalogoPolicy
{
    public function create(User $user): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function update(User $user, Catalogo $catalogo): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function delete(User $user, Catalogo $catalogo): bool
    {
        return $user->can('core_institucional.administrar');
    }
}
