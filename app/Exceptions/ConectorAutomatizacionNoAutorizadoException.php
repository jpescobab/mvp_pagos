<?php

namespace App\Exceptions;

use App\Models\ConectorAutomatizacionNavegador;
use RuntimeException;

class ConectorAutomatizacionNoAutorizadoException extends RuntimeException
{
    public static function paraConector(ConectorAutomatizacionNavegador $conector): self
    {
        return new self("El conector '{$conector->codigo}' no está activo o no tiene autorización registrada para ejecutar automatización de navegador.");
    }
}
