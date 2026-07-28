<?php

namespace App\Policies;

use App\Models\EjecucionInformeRazonado;
use App\Models\MetricaInformeRazonado;
use App\Models\User;

class MetricaInformeRazonadoPolicy
{
    public function create(User $user, EjecucionInformeRazonado $ejecucion): bool
    {
        return $user->can('informes.elaborar') && $ejecucion->estaEnElaboracion();
    }

    public function update(User $user, MetricaInformeRazonado $metrica): bool
    {
        return $user->can('informes.elaborar') && $this->ejecucionEnElaboracion($metrica);
    }

    public function delete(User $user, MetricaInformeRazonado $metrica): bool
    {
        return $user->can('informes.elaborar') && $this->ejecucionEnElaboracion($metrica);
    }

    private function ejecucionEnElaboracion(MetricaInformeRazonado $metrica): bool
    {
        return (bool) $metrica->ejecucionInformeRazonado?->estaEnElaboracion();
    }
}
