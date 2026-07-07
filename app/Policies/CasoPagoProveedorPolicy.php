<?php

namespace App\Policies;

use App\Models\CasoPagoProveedor;
use App\Models\User;

class CasoPagoProveedorPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CasoPagoProveedor $caso): bool
    {
        return true;
    }

    public function vincularAdquisicion(User $user, CasoPagoProveedor $caso): bool
    {
        return $user->can('pago_proveedores.vincular_adquisicion');
    }

    public function registrarCgu(User $user, CasoPagoProveedor $caso): bool
    {
        return $user->can('pago_proveedores.registrar_cgu');
    }

    public function registrarPagoBancario(User $user, CasoPagoProveedor $caso): bool
    {
        return $user->can('pago_proveedores.pagar');
    }

    public function registrarFactura(User $user, CasoPagoProveedor $caso): bool
    {
        return $user->can('pago_proveedores.registrar_factura');
    }

    public function verificarCasoSgf(User $user): bool
    {
        return $user->can('pago_proveedores.verificar_caso_sgf');
    }

    public function importarCasosSgf(User $user): bool
    {
        return $user->can('pago_proveedores.importar_casos_sgf');
    }
}
