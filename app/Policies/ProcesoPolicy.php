<?php

namespace App\Policies;

use App\Enums\PagoProveedores\InstanciaRevision;
use App\Models\CasoPagoProveedor;
use App\Models\Proceso;
use App\Models\User;

class ProcesoPolicy
{
    public function gestionarDocumentos(User $user, Proceso $proceso): bool
    {
        return $user->can('documentos.gestionar');
    }

    /**
     * Mientras el caso está en una instancia de revisión de pagos, validar o
     * rechazar documentos SHALL hacerse desde Revisión de Pagos (que valida
     * por instancia), no desde el endpoint genérico de documentos.
     */
    public function validarDocumentos(User $user, Proceso $proceso): bool
    {
        if (! $user->can('documentos.validar')) {
            return false;
        }

        if ($proceso->sujeto instanceof CasoPagoProveedor
            && InstanciaRevision::desdeEstado($proceso->estadoActual->codigo) !== null) {
            return false;
        }

        return true;
    }
}
