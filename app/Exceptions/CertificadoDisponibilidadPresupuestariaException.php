<?php

namespace App\Exceptions;

use RuntimeException;

class CertificadoDisponibilidadPresupuestariaException extends RuntimeException
{
    public static function lineaPresupuestoInexistente(): self
    {
        return new self('La línea de presupuesto indicada no existe.');
    }

    public static function noEditableEnEstado(string $estado): self
    {
        return new self("Un CDP solo puede editarse en estado borrador (estado actual: {$estado}).");
    }

    public static function noFirmadoParaAnular(string $estado): self
    {
        return new self("Solo puede anularse un CDP en estado firmado (estado actual: {$estado}).");
    }

    public static function sinIndicadorParaFecha(string $codigo, string $fecha): self
    {
        return new self("No hay un valor de {$codigo} registrado para la fecha {$fecha}.");
    }
}
