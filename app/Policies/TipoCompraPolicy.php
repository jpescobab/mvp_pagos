<?php

namespace App\Policies;

use App\Models\TipoCompra;
use App\Models\User;

class TipoCompraPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('pago_proveedores.administrar_tipos_compra');
    }

    public function view(User $user, TipoCompra $tipoCompra): bool
    {
        return $user->can('pago_proveedores.administrar_tipos_compra');
    }

    public function create(User $user): bool
    {
        return $user->can('pago_proveedores.administrar_tipos_compra');
    }

    public function update(User $user, TipoCompra $tipoCompra): bool
    {
        return $user->can('pago_proveedores.administrar_tipos_compra');
    }

    public function delete(User $user, TipoCompra $tipoCompra): bool
    {
        return $user->can('pago_proveedores.administrar_tipos_compra');
    }
}
