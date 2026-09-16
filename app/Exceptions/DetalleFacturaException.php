<?php

namespace App\Exceptions;

use RuntimeException;

class DetalleFacturaException extends RuntimeException
{
    private string $campo = 'factura';

    public static function facturaYaTieneDetalle(): self
    {
        return new self('Esta factura ya tiene un detalle registrado.');
    }

    public static function sinDetalleAun(): self
    {
        return new self('Esta factura todavía no tiene un detalle registrado.');
    }

    public function campo(): string
    {
        return $this->campo;
    }
}
