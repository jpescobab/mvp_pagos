<?php

namespace App\Policies\Presupuesto;

use App\Models\User;

class PresupuestoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('presupuesto.consultar');
    }
}
