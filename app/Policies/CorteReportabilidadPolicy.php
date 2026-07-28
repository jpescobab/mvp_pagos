<?php

namespace App\Policies;

use App\Models\CorteReportabilidad;
use App\Models\User;

class CorteReportabilidadPolicy
{
    /**
     * Generar (poblar) el contenido de un corte. El estado del corte
     * (`borrador` vs `publicado`) lo valida el service; aquí solo el permiso.
     */
    public function generar(User $user, CorteReportabilidad $corte): bool
    {
        return $user->can('reportabilidad.generar_corte');
    }
}
