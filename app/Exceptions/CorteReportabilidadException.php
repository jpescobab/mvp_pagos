<?php

namespace App\Exceptions;

use RuntimeException;

class CorteReportabilidadException extends RuntimeException
{
    public static function sinPermiso(string $permiso): self
    {
        return new self("El usuario no tiene el permiso requerido ('{$permiso}') para publicar este corte.");
    }

    public static function corteYaPublicado(): self
    {
        return new self('Este corte ya fue publicado y no admite nuevos items ni snapshots.');
    }

    public static function corteNoPublicado(): self
    {
        return new self('No se puede iniciar un informe sobre un corte que aún no ha sido publicado.');
    }
}
