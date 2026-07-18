<?php

namespace App\Exceptions;

use RuntimeException;

class OrdenCompraSinProveedorException extends RuntimeException
{
    public function __construct(string $message = 'La Orden de Compra no tiene un RUT de proveedor identificable en Mercado Público; no es posible guardarla automáticamente.')
    {
        parent::__construct($message);
    }
}
