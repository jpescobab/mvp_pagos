<?php

namespace App\Services\Indicadores;

use App\Models\IndicadorEconomico;
use Carbon\CarbonInterface;

class IndicadorEconomicoSelector
{
    /**
     * Select a daily indicator (UF, USD) by its exact fecha_valor. For USD,
     * apply the configured fallback rule when no exact match exists.
     */
    public function paraFecha(string $tipo, CarbonInterface $fecha): ?IndicadorEconomico
    {
        $indicador = IndicadorEconomico::where('tipo', $tipo)
            ->whereDate('fecha_valor', $fecha->toDateString())
            ->first();

        if ($indicador !== null || $tipo !== 'USD') {
            return $indicador;
        }

        return $this->aplicarFallbackUsd($fecha);
    }

    /**
     * Select a periodic indicator (UTM, UTA, IPC) by its periodo (YYYY-MM).
     */
    public function paraPeriodo(string $tipo, string $periodo): ?IndicadorEconomico
    {
        return IndicadorEconomico::where('tipo', $tipo)->where('periodo', $periodo)->first();
    }

    private function aplicarFallbackUsd(CarbonInterface $fecha): ?IndicadorEconomico
    {
        $estrategia = config('indicadores.usd_fallback');

        return match ($estrategia) {
            'ultimo_valor_disponible' => IndicadorEconomico::where('tipo', 'USD')
                ->whereDate('fecha_valor', '<=', $fecha->toDateString())
                ->orderByDesc('fecha_valor')
                ->first(),
            default => null,
        };
    }
}
