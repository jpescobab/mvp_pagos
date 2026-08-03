<?php

namespace App\Exceptions;

use RuntimeException;

class ContratoSinProveedorException extends RuntimeException
{
    public function __construct(string $message = 'No fue posible identificar un RUT de proveedor para el contrato; indica un proveedor existente o los datos completos del proveedor.')
    {
        parent::__construct($message);
    }
}
