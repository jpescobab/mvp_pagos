<?php

namespace App\Exceptions;

use RuntimeException;

class ProcesoAdquisicionException extends RuntimeException
{
    private string $campo = 'modalidad_id';

    public static function modalidadInvalida(): self
    {
        return new self('La modalidad indicada no existe o no está activa.');
    }

    public static function noEditableEnEstado(string $estado): self
    {
        return new self("Un proceso de adquisición solo puede editarse en estado borrador (estado actual: {$estado}).");
    }

    public static function montoSobreUmbralUtm(): self
    {
        $excepcion = new self('El monto estimado debe ser menor a 1.000 UTM. Para montos mayores corresponde Licitación Pública.');
        $excepcion->campo = 'monto_estimado_solicitado';

        return $excepcion;
    }

    public static function sinIndicadorParaFecha(string $moneda, string $fecha): self
    {
        $excepcion = new self("No hay un valor de {$moneda} registrado para la fecha {$fecha}.");
        $excepcion->campo = 'fecha_paridad';

        return $excepcion;
    }

    public function campo(): string
    {
        return $this->campo;
    }
}
