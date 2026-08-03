<?php

namespace App\Policies;

use App\Models\Contrato;
use App\Models\User;

class ContratoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('contratos.ver');
    }

    public function view(User $user, Contrato $contrato): bool
    {
        return $user->can('contratos.ver');
    }

    public function create(User $user): bool
    {
        return $user->can('contratos.crear');
    }

    public function update(User $user, Contrato $contrato): bool
    {
        return $user->can('contratos.editar');
    }

    public function vincular(User $user, Contrato $contrato): bool
    {
        return $user->can('contratos.editar');
    }

    public function vincularPago(User $user, Contrato $contrato): bool
    {
        return $user->can('contratos.vincular_pago');
    }
}
