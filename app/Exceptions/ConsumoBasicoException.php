<?php

namespace App\Exceptions;

use RuntimeException;

class ConsumoBasicoException extends RuntimeException
{
    private string $campo = 'caso_pago_proveedor_id';

    public static function casoYaTieneConsumo(): self
    {
        return new self('Este caso de pago ya tiene un detalle de consumo registrado.');
    }

    public function campo(): string
    {
        return $this->campo;
    }
}
