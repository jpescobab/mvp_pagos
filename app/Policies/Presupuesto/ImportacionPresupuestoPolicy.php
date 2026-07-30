<?php

namespace App\Policies\Presupuesto;

use App\Models\User;

class ImportacionPresupuestoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('presupuesto.consultar');
    }

    public function create(User $user): bool
    {
        return $user->can('presupuesto.importar');
    }
}
