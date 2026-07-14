<?php

namespace App\Policies;

use App\Models\TipoProcesoPago;
use App\Models\User;

class TipoProcesoPagoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('pago_proveedores.administrar_requisitos_documentales');
    }

    public function view(User $user, TipoProcesoPago $tipoProcesoPago): bool
    {
        return $user->can('pago_proveedores.administrar_requisitos_documentales');
    }

    public function create(User $user): bool
    {
        return $user->can('pago_proveedores.administrar_requisitos_documentales');
    }

    public function update(User $user, TipoProcesoPago $tipoProcesoPago): bool
    {
        return $user->can('pago_proveedores.administrar_requisitos_documentales');
    }

    public function delete(User $user, TipoProcesoPago $tipoProcesoPago): bool
    {
        return $user->can('pago_proveedores.administrar_requisitos_documentales');
    }
}
