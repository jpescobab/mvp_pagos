<?php

namespace App\Policies\Presupuesto;

use App\Models\Presupuesto\CertificadoDisponibilidadPresupuestaria;
use App\Models\User;

class CertificadoDisponibilidadPresupuestariaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('presupuesto.consultar');
    }

    public function view(User $user, CertificadoDisponibilidadPresupuestaria $cdp): bool
    {
        return $user->can('presupuesto.consultar');
    }

    public function create(User $user): bool
    {
        return $user->can('presupuesto.crear_cdp');
    }

    public function update(User $user, CertificadoDisponibilidadPresupuestaria $cdp): bool
    {
        return $user->can('presupuesto.crear_cdp');
    }
}
