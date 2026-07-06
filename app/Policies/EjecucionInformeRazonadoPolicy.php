<?php

namespace App\Policies;

use App\Models\EjecucionInformeRazonado;
use App\Models\User;

class EjecucionInformeRazonadoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('informes.ver');
    }

    public function view(User $user, EjecucionInformeRazonado $ejecucionInformeRazonado): bool
    {
        return $user->can('informes.ver');
    }
}
