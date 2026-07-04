<?php

namespace App\Enums\Maestros;

enum Moneda: string
{
    case Clp = 'clp';
    case Usd = 'usd';

    public function label(): string
    {
        return match ($this) {
            self::Clp => 'CLP — Peso chileno',
            self::Usd => 'USD — Dólar estadounidense',
        };
    }
}
