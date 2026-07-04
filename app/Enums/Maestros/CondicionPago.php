<?php

namespace App\Enums\Maestros;

enum CondicionPago: string
{
    case Contado = 'contado';
    case Dias30 = 'dias_30';
    case Dias45 = 'dias_45';
    case Dias60 = 'dias_60';
    case Dias90 = 'dias_90';

    public function label(): string
    {
        return match ($this) {
            self::Contado => 'Contado',
            self::Dias30 => '30 días',
            self::Dias45 => '45 días',
            self::Dias60 => '60 días',
            self::Dias90 => '90 días',
        };
    }
}
