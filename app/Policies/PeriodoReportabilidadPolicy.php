<?php

namespace App\Policies;

use App\Models\PeriodoReportabilidad;
use App\Models\User;

class PeriodoReportabilidadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reportabilidad.ver');
    }

    public function view(User $user, PeriodoReportabilidad $periodoReportabilidad): bool
    {
        return $user->can('reportabilidad.ver');
    }
}
