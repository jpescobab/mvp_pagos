<?php

namespace App\Policies;

use App\Models\EgresoCgu;
use App\Models\User;

class EgresoCguPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, EgresoCgu $egreso): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('pago_proveedores.registrar_egreso');
    }

    public function gestionarDocumentos(User $user, EgresoCgu $egreso): bool
    {
        return $user->can('documentos.gestionar');
    }
}
