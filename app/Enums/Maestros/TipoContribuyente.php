<?php

namespace App\Enums\Maestros;

enum TipoContribuyente: string
{
    case PersonaNatural = 'persona_natural';
    case PersonaJuridica = 'persona_juridica';

    public function label(): string
    {
        return match ($this) {
            self::PersonaNatural => 'Persona natural',
            self::PersonaJuridica => 'Persona jurídica',
        };
    }
}
