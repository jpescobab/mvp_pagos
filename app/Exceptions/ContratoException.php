<?php

namespace App\Exceptions;

use RuntimeException;

class ContratoException extends RuntimeException
{
    private string $campo = 'contrato';

    public static function noEditableEnEstado(string $estado): self
    {
        return new self("Un contrato solo puede editarse en estado borrador (estado actual: {$estado}).");
    }

    public static function convenioPrecioNoHabilitado(): self
    {
        $excepcion = new self('Este contrato no tiene habilitado el convenio de precios (tiene_convenio_precio = false).');
        $excepcion->campo = 'tiene_convenio_precio';

        return $excepcion;
    }

    public static function calendarioPagoNoHabilitado(): self
    {
        $excepcion = new self('Este contrato no tiene habilitado el calendario de pago (tiene_calendario_pago = false).');
        $excepcion->campo = 'tiene_calendario_pago';

        return $excepcion;
    }

    public static function montoTotalRequeridoParaCalendario(): self
    {
        $excepcion = new self('Se requiere el monto total del contrato para generar el calendario de pago.');
        $excepcion->campo = 'monto_total';

        return $excepcion;
    }

    public static function cuotaYaVinculada(): self
    {
        $excepcion = new self('Esta cuota ya está vinculada a un caso de pago a proveedores.');
        $excepcion->campo = 'caso_pago_proveedor_id';

        return $excepcion;
    }

    public function campo(): string
    {
        return $this->campo;
    }
}
