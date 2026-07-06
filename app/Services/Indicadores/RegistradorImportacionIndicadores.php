<?php

namespace App\Services\Indicadores;

use App\Models\IndicadorEconomicoImportacion;

/**
 * Acumula los conteos de una única ejecución de importación (recibidos,
 * creados, omitidos, fallidos) y transiciona su estado pending -> running ->
 * success/partial_success/failed. Una instancia por ejecución, no compartida.
 */
class RegistradorImportacionIndicadores
{
    private int $recibidos = 0;

    private int $creados = 0;

    private int $omitidos = 0;

    private int $fallidos = 0;

    /** @var list<string> */
    private array $errores = [];

    /** @var list<string> */
    private array $advertencias = [];

    /**
     * @param  array<string, mixed>  $atributos
     */
    public function iniciar(array $atributos): IndicadorEconomicoImportacion
    {
        $importacion = IndicadorEconomicoImportacion::create([
            'estado' => 'pending',
            ...$atributos,
        ]);

        $importacion->marcarComoRunning();

        return $importacion;
    }

    public function recibido(): void
    {
        $this->recibidos++;
    }

    public function creado(): void
    {
        $this->creados++;
    }

    public function omitido(): void
    {
        $this->omitidos++;
    }

    public function fallido(string $mensaje): void
    {
        $this->fallidos++;
        $this->errores[] = $mensaje;
    }

    public function advertir(string $mensaje): void
    {
        $this->advertencias[] = $mensaje;
    }

    public function finalizar(IndicadorEconomicoImportacion $importacion): IndicadorEconomicoImportacion
    {
        $estado = match (true) {
            $this->fallidos > 0 && ($this->creados > 0 || $this->omitidos > 0) => 'partial_success',
            $this->fallidos > 0 => 'failed',
            default => 'success',
        };

        $importacion->marcarComoFinalizada($estado, [
            'total_recibidos' => $this->recibidos,
            'total_creados' => $this->creados,
            'total_omitidos' => $this->omitidos,
            'total_fallidos' => $this->fallidos,
            'errores' => $this->errores === [] ? null : $this->errores,
            'advertencias' => $this->advertencias === [] ? null : $this->advertencias,
        ]);

        return $importacion->refresh();
    }
}
