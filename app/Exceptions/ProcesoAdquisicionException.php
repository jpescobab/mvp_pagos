<?php

namespace App\Exceptions;

use RuntimeException;

class ProcesoAdquisicionException extends RuntimeException
{
    public static function modalidadInvalida(): self
    {
        return new self('La modalidad indicada no existe o no está activa.');
    }
}
