<?php

namespace App\Policies;

use App\Models\ConsumoBasico;
use App\Models\User;

class ConsumoBasicoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('consumo_basico.ver');
    }

    public function view(User $user, ConsumoBasico $consumoBasico): bool
    {
        return $user->can('consumo_basico.ver');
    }

    public function create(User $user): bool
    {
        return $user->can('consumo_basico.crear');
    }

    public function update(User $user, ConsumoBasico $consumoBasico): bool
    {
        return $user->can('consumo_basico.editar');
    }
}
