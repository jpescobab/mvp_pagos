<?php

namespace App\Exceptions;

use RuntimeException;

class WorkflowTransitionException extends RuntimeException
{
    public static function moduloInactivo(): self
    {
        return new self('El workflow de este proceso está inactivo.');
    }

    public static function procesoCerrado(): self
    {
        return new self('El proceso ya está cerrado.');
    }

    public static function transicionNoPermitida(string $codigo): self
    {
        return new self("La transición '{$codigo}' no es válida desde el estado actual del proceso.");
    }

    public static function sinPermiso(string $permiso): self
    {
        return new self("El usuario no tiene el permiso requerido ('{$permiso}') para ejecutar esta transición.");
    }

    public static function comentarioRequerido(): self
    {
        return new self('Esta transición requiere un comentario.');
    }

    /**
     * @param  list<string>  $faltantes
     */
    public static function documentosFaltantes(array $faltantes): self
    {
        return new self('Faltan documentos obligatorios para esta transición: '.implode(', ', $faltantes));
    }
}
