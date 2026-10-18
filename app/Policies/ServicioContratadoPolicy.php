<?php

namespace App\Policies;

use App\Models\ServicioContratado;
use App\Models\User;

class ServicioContratadoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('servicios_contratados.ver');
    }

    public function view(User $user, ServicioContratado $servicio): bool
    {
        return $user->can('servicios_contratados.ver');
    }

    public function create(User $user): bool
    {
        return $user->can('servicios_contratados.crear');
    }

    public function update(User $user, ServicioContratado $servicio): bool
    {
        return $user->can('servicios_contratados.editar');
    }

    public function terminar(User $user, ServicioContratado $servicio): bool
    {
        return $user->can('servicios_contratados.terminar');
    }
}
