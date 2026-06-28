<?php

namespace App\Policies;

use App\Models\Proceso;
use App\Models\User;

class ProcesoPolicy
{
    public function gestionarDocumentos(User $user, Proceso $proceso): bool
    {
        return $user->can('documentos.gestionar');
    }

    public function validarDocumentos(User $user, Proceso $proceso): bool
    {
        return $user->can('documentos.validar');
    }
}
