<?php

namespace App\Policies;

use App\Models\EjecucionInformeRazonado;
use App\Models\GraficoInformeRazonado;
use App\Models\User;

class GraficoInformeRazonadoPolicy
{
    public function create(User $user, EjecucionInformeRazonado $ejecucion): bool
    {
        return $user->can('informes.elaborar') && $ejecucion->estaEnElaboracion();
    }

    public function update(User $user, GraficoInformeRazonado $grafico): bool
    {
        return $user->can('informes.elaborar') && $this->ejecucionEnElaboracion($grafico);
    }

    public function delete(User $user, GraficoInformeRazonado $grafico): bool
    {
        return $user->can('informes.elaborar') && $this->ejecucionEnElaboracion($grafico);
    }

    private function ejecucionEnElaboracion(GraficoInformeRazonado $grafico): bool
    {
        return (bool) $grafico->ejecucionInformeRazonado?->estaEnElaboracion();
    }
}
