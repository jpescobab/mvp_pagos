<?php

namespace App\Policies;

use App\Models\TrabajoIntegracion;
use App\Models\User;

class TrabajoIntegracionPolicy
{
    /**
     * Eliminar una corrida de importación SGF. Solo la elegibilidad de permiso;
     * la guardia de trazabilidad (sin snapshots, no en progreso) la impone el
     * servicio de eliminación.
     */
    public function eliminar(User $user, TrabajoIntegracion $trabajo): bool
    {
        return $user->can('integraciones_sgf.eliminar_importacion');
    }
}
