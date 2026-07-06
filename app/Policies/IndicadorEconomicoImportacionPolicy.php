<?php

namespace App\Policies;

use App\Models\User;

class IndicadorEconomicoImportacionPolicy
{
    public function importar(User $user): bool
    {
        return $user->can('indicadores.importar');
    }
}
