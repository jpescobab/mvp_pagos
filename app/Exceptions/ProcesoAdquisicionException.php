<?php

namespace App\Exceptions;

use RuntimeException;

class ProcesoAdquisicionException extends RuntimeException
{
    public static function modalidadInvalida(): self
    {
        return new self('La modalidad indicada no existe o no está activa.');
    }

    public static function noEditableEnEstado(string $estado): self
    {
        return new self("Un proceso de adquisición solo puede editarse en estado borrador (estado actual: {$estado}).");
    }
}
