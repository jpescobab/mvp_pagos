<?php

namespace App\Policies;

use App\Models\EjecucionInformeRazonado;
use App\Models\SeccionInformeRazonado;
use App\Models\User;

class SeccionInformeRazonadoPolicy
{
    public function create(User $user, EjecucionInformeRazonado $ejecucion): bool
    {
        return $user->can('informes.elaborar') && $ejecucion->estaEnElaboracion();
    }

    public function update(User $user, SeccionInformeRazonado $seccion): bool
    {
        return $user->can('informes.elaborar') && $this->ejecucionEnElaboracion($seccion);
    }

    public function delete(User $user, SeccionInformeRazonado $seccion): bool
    {
        return $user->can('informes.elaborar') && $this->ejecucionEnElaboracion($seccion);
    }

    private function ejecucionEnElaboracion(SeccionInformeRazonado $seccion): bool
    {
        return (bool) $seccion->ejecucionInformeRazonado?->estaEnElaboracion();
    }
}
