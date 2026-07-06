<?php

namespace App\Policies;

use App\Models\OrdenCompraMercadoPublico;
use App\Models\User;

class OrdenCompraMercadoPublicoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('adquisiciones.consultar_orden_compra_mp');
    }

    public function view(User $user, OrdenCompraMercadoPublico $orden): bool
    {
        return $user->can('adquisiciones.consultar_orden_compra_mp');
    }

    public function create(User $user): bool
    {
        return $user->can('adquisiciones.consultar_orden_compra_mp');
    }

    public function vincularProcesoAdquisicion(User $user, OrdenCompraMercadoPublico $orden): bool
    {
        return $user->can('adquisiciones.consultar_orden_compra_mp');
    }
}
