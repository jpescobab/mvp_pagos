<?php

namespace App\Policies;

use App\Models\EjecucionInformeRazonado;
use App\Models\ExcepcionInformeRazonado;
use App\Models\User;

class ExcepcionInformeRazonadoPolicy
{
    public function create(User $user, EjecucionInformeRazonado $ejecucion): bool
    {
        return $user->can('informes.elaborar') && $ejecucion->estaEnElaboracion();
    }

    public function update(User $user, ExcepcionInformeRazonado $excepcion): bool
    {
        return $user->can('informes.elaborar') && $this->ejecucionEnElaboracion($excepcion);
    }

    public function delete(User $user, ExcepcionInformeRazonado $excepcion): bool
    {
        return $user->can('informes.elaborar') && $this->ejecucionEnElaboracion($excepcion);
    }

    private function ejecucionEnElaboracion(ExcepcionInformeRazonado $excepcion): bool
    {
        return (bool) $excepcion->ejecucionInformeRazonado?->estaEnElaboracion();
    }
}
