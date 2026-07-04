<?php

namespace App\Enums\Maestros;

enum TipoCuentaBancaria: string
{
    case CuentaCorriente = 'cuenta_corriente';
    case CuentaVista = 'cuenta_vista';
    case CuentaAhorro = 'cuenta_ahorro';

    public function label(): string
    {
        return match ($this) {
            self::CuentaCorriente => 'Cuenta corriente',
            self::CuentaVista => 'Cuenta vista',
            self::CuentaAhorro => 'Cuenta de ahorro',
        };
    }
}
