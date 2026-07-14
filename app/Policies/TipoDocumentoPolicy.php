<?php

namespace App\Policies;

use App\Models\TipoDocumento;
use App\Models\User;

class TipoDocumentoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function view(User $user, TipoDocumento $tipoDocumento): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function create(User $user): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function update(User $user, TipoDocumento $tipoDocumento): bool
    {
        return $user->can('core_institucional.administrar');
    }

    public function delete(User $user, TipoDocumento $tipoDocumento): bool
    {
        return $user->can('core_institucional.administrar');
    }
}
