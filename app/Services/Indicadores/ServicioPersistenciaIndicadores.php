<?php

namespace App\Services\Indicadores;

use App\Models\IndicadorEconomico;

class ServicioPersistenciaIndicadores
{
    public function __construct(private readonly IndicadorEconomicoSelector $selector) {}

    /**
     * Crea el indicador solo si no existe ya un registro con la misma llave
     * (codigo, fecha_valor, periodo, fuente, es_proyectado). Nunca actualiza
     * un registro existente.
     *
     * @param  array<string, mixed>  $atributos
     * @return array{indicador: IndicadorEconomico, creado: bool}
     */
    public function crearSiNoExiste(array $atributos): array
    {
        $atributos = [
            'fecha_valor' => null,
            'periodo' => null,
            'es_proyectado' => false,
            'es_oficial' => true,
            'activo' => true,
            ...$atributos,
        ];

        $llave = array_intersect_key(
            $atributos,
            array_flip(['codigo', 'fecha_valor', 'periodo', 'fuente', 'es_proyectado']),
        );

        $indicador = IndicadorEconomico::firstOrCreate($llave, $atributos);

        if ($indicador->wasRecentlyCreated) {
            $this->selector->invalidarUltimoPorTipo($indicador->codigo);
        }

        return ['indicador' => $indicador, 'creado' => $indicador->wasRecentlyCreated];
    }
}
