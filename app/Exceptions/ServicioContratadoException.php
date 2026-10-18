<?php

namespace App\Exceptions;

use RuntimeException;

class ServicioContratadoException extends RuntimeException
{
    private function __construct(string $mensaje, private readonly string $campo)
    {
        parent::__construct($mensaje);
    }

    public static function contratoNoAprobado(): self
    {
        return new self('El contrato debe estar aprobado para registrarle servicios.', 'contrato_id');
    }

    public static function vigenciaInvalida(): self
    {
        return new self('La vigencia del servicio debe estar contenida en la del contrato y terminar después de iniciar.', 'fecha_inicio');
    }

    public static function servicioTerminado(): self
    {
        return new self('Un servicio terminado no puede modificarse.', 'servicio');
    }

    public function campo(): string
    {
        return $this->campo;
    }
}
