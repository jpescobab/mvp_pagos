<?php

namespace App\Services\Indicadores;

use App\Models\IndicadorEconomico;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

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

    /**
     * Latest registered value per tipo, for display chips (login, dashboard).
     *
     * @param  list<string>  $tipos
     * @return list<array{tipo: string, valor: string, fecha_valor: ?string, periodo: ?string}>
     */
    public function ultimosPorTipo(array $tipos): array
    {
        $resultado = [];

        foreach ($tipos as $tipo) {
            $indicador = IndicadorEconomico::where('tipo', $tipo)
                ->orderByDesc('fecha_valor')
                ->orderByDesc('periodo')
                ->first();

            if ($indicador === null) {
                continue;
            }

            $fechaValor = $indicador->fecha_valor;

            $resultado[] = [
                'tipo' => $indicador->tipo,
                'valor' => (string) $indicador->valor,
                'fecha_valor' => $fechaValor === null ? null : Carbon::parse($fechaValor)->toDateString(),
                'periodo' => $indicador->periodo,
            ];
        }

        return $resultado;
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
