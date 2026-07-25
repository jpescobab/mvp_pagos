<?php

namespace App\Policies;

use App\Models\ProcesoAdquisicion;
use App\Models\User;

class ProcesoAdquisicionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('adquisiciones.consultar_proceso');
    }

    public function view(User $user, ProcesoAdquisicion $proceso): bool
    {
        return $user->can('adquisiciones.consultar_proceso');
    }

    public function create(User $user): bool
    {
        return $user->can('adquisiciones.crear_proceso');
    }

    public function update(User $user, ProcesoAdquisicion $proceso): bool
    {
        return $user->can('adquisiciones.editar_proceso');
    }
}
