<?php

namespace App\Policies;

use App\Models\ConectorAutomatizacionNavegador;
use App\Models\User;

class ConectorAutomatizacionNavegadorPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ConectorAutomatizacionNavegador $conector): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('integraciones.gestionar_conectores');
    }

    public function gestionar(User $user, ConectorAutomatizacionNavegador $conector): bool
    {
        return $user->can('integraciones.gestionar_conectores');
    }
}
