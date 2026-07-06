<?php

namespace App\Services\Indicadores;

class ServicioNormalizadorIndicadores
{
    /**
     * Normaliza un valor crudo ("40.814,87", "$68.785", "0,4%") a decimal,
     * quitando símbolo de moneda, espacios, porcentaje y puntos de miles, y
     * cambiando la coma decimal por punto.
     */
    public function normalizarValor(string $crudo): float
    {
        $limpio = str_replace(['$', '%', ' '], '', $crudo);
        $limpio = str_replace(['.', ','], ['', '.'], $limpio);

        return (float) $limpio;
    }
}
