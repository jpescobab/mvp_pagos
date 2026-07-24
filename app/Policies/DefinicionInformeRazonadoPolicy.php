<?php

namespace App\Policies;

use App\Models\DefinicionInformeRazonado;
use App\Models\User;

class DefinicionInformeRazonadoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('informes.ver');
    }

    public function view(User $user, DefinicionInformeRazonado $definicionInformeRazonado): bool
    {
        return $user->can('informes.ver');
    }

    public function create(User $user): bool
    {
        return $user->can('informes.administrar');
    }

    public function update(User $user, DefinicionInformeRazonado $definicionInformeRazonado): bool
    {
        return $user->can('informes.administrar');
    }

    public function delete(User $user, DefinicionInformeRazonado $definicionInformeRazonado): bool
    {
        return $user->can('informes.administrar');
    }
}
