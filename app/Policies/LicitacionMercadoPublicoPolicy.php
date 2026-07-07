<?php

namespace App\Policies;

use App\Models\LicitacionMercadoPublico;
use App\Models\User;

class LicitacionMercadoPublicoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('adquisiciones.consultar_licitacion_mp');
    }

    public function view(User $user, LicitacionMercadoPublico $licitacion): bool
    {
        return $user->can('adquisiciones.consultar_licitacion_mp');
    }

    public function create(User $user): bool
    {
        return $user->can('adquisiciones.consultar_licitacion_mp');
    }

    public function vincularProcesoAdquisicion(User $user, LicitacionMercadoPublico $licitacion): bool
    {
        return $user->can('adquisiciones.consultar_licitacion_mp');
    }
}
