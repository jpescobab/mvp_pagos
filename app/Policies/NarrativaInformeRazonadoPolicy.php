<?php

namespace App\Policies;

use App\Models\EjecucionInformeRazonado;
use App\Models\NarrativaInformeRazonado;
use App\Models\User;

class NarrativaInformeRazonadoPolicy
{
    public function create(User $user, EjecucionInformeRazonado $ejecucion): bool
    {
        return $user->can('informes.elaborar') && $ejecucion->estaEnElaboracion();
    }

    public function update(User $user, NarrativaInformeRazonado $narrativa): bool
    {
        return $user->can('informes.elaborar') && $this->ejecucionEnElaboracion($narrativa);
    }

    public function delete(User $user, NarrativaInformeRazonado $narrativa): bool
    {
        return $user->can('informes.elaborar') && $this->ejecucionEnElaboracion($narrativa);
    }

    public function revisar(User $user, NarrativaInformeRazonado $narrativa): bool
    {
        return $user->can('informes.aprobar');
    }

    private function ejecucionEnElaboracion(NarrativaInformeRazonado $narrativa): bool
    {
        return (bool) $narrativa->ejecucionInformeRazonado?->estaEnElaboracion();
    }
}
