<?php

namespace App\Policies;

use App\Models\Proveedor;
use App\Models\User;

class ProveedorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function view(User $user, Proveedor $proveedor): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function create(User $user): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function update(User $user, Proveedor $proveedor): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function delete(User $user, Proveedor $proveedor): bool
    {
        return $user->can('core_institucional.administrar');
    }
}
