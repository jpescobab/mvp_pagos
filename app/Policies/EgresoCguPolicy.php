<?php

namespace App\Policies;

use App\Models\EgresoCgu;
use App\Models\User;

class EgresoCguPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, EgresoCgu $egreso): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('pago_proveedores.registrar_egreso');
    }

    public function gestionarDocumentos(User $user, EgresoCgu $egreso): bool
    {
        return $user->can('documentos.gestionar');
    }

    /**
     * Puede abrir/revisar el egreso si opera alguna de las dos instancias
     * de revisión. El Administrador Zonal, además, queda acotado a su zona.
     */
    public function revisar(User $user, EgresoCgu $egreso): bool
    {
        return $this->revisarFinanzas($user, $egreso) || $this->revisarZonal($user, $egreso);
    }

    public function revisarFinanzas(User $user, EgresoCgu $egreso): bool
    {
        return $user->can('pago_proveedores.revisar_finanzas');
    }

    /**
     * El Administrador Zonal solo revisa egresos de su propia jurisdicción/zona.
     */
    public function revisarZonal(User $user, EgresoCgu $egreso): bool
    {
        return $user->can('pago_proveedores.revisar_zonal')
            && $this->mismaJurisdiccion($user, $egreso);
    }

    /**
     * La jurisdicción del egreso (derivada de su centro financiero) debe
     * coincidir con la del usuario (vía funcionario -> cfinanciero).
     */
    private function mismaJurisdiccion(User $user, EgresoCgu $egreso): bool
    {
        $jurisdiccionEgreso = $egreso->jurisdiccionId();
        $jurisdiccionUsuario = $user->funcionario?->cfinanciero?->jurisdiccion_id;

        return $jurisdiccionEgreso !== null
            && $jurisdiccionUsuario !== null
            && $jurisdiccionEgreso === $jurisdiccionUsuario;
    }
}
