<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('usuarios.ver');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('usuarios.ver');
    }

    public function create(User $user): bool
    {
        return $user->can('usuarios.crear');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('usuarios.editar');
    }

    /**
     * Eliminación física de usuarios prohibida: los usuarios se desactivan, nunca se borran.
     */
    public function delete(User $user, User $model): bool
    {
        return false;
    }

    public function activar(User $user, User $model): bool
    {
        return $user->can('usuarios.activar');
    }

    public function desactivar(User $user, User $model): bool
    {
        return $user->can('usuarios.desactivar');
    }

    public function resetearPassword(User $user, User $model): bool
    {
        return $user->can('usuarios.resetear_password');
    }

    public function asignarRoles(User $user, User $model): bool
    {
        return $user->can('usuarios.asignar_roles');
    }
}
