<?php

namespace App\Policies;

use App\Models\ClienteMedidor;
use App\Models\User;

class ClienteMedidorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function view(User $user, ClienteMedidor $clienteMedidor): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function create(User $user): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function update(User $user, ClienteMedidor $clienteMedidor): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function delete(User $user, ClienteMedidor $clienteMedidor): bool
    {
        return $user->can('core_institucional.administrar');
    }
}
