<?php

namespace App\Services\Indicadores;

use App\Models\IndicadorEconomico;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class IndicadorEconomicoSelector
{
    private const CACHE_TTL_MINUTOS = 5;

    private const CACHE_MISS = '__miss__';

    /**
     * Select a daily indicator (UF, USD) by its exact fecha_valor. For USD,
     * apply the configured fallback rule when no exact match exists.
     */
    public function paraFecha(string $codigo, CarbonInterface $fecha): ?IndicadorEconomico
    {
        $indicador = IndicadorEconomico::where('codigo', $codigo)
            ->whereDate('fecha_valor', $fecha->toDateString())
            ->first();

        if ($indicador !== null || $codigo !== 'USD') {
            return $indicador;
        }

        return $this->aplicarFallbackUsd($fecha);
    }

    /**
     * Select a periodic indicator (UTM, UTA, IPC) by its periodo (YYYY-MM).
     */
    public function paraPeriodo(string $codigo, string $periodo): ?IndicadorEconomico
    {
        return IndicadorEconomico::where('codigo', $codigo)->where('periodo', $periodo)->first();
    }

    /**
     * Latest registered value per codigo, for display chips (login, dashboard).
     * Cached per codigo (TTL corto) para no recalcularlo en cada request; los
     * códigos sin entrada vigente se resuelven en una sola consulta.
     *
     * @param  list<string>  $codigos
     * @return list<array{codigo: string, valor: string, fecha_valor: ?string, periodo: ?string}>
     */
    public function ultimosPorTipo(array $codigos): array
    {
        $porCodigo = [];
        $faltantes = [];

        foreach ($codigos as $codigo) {
            $valor = Cache::get($this->cacheKeyUltimo($codigo), self::CACHE_MISS);

            if ($valor === self::CACHE_MISS) {
                $faltantes[] = $codigo;

                continue;
            }

            $porCodigo[$codigo] = $valor;
        }

        if ($faltantes !== []) {
            $resueltos = $this->resolverUltimosPorTipo($faltantes);

            foreach ($faltantes as $codigo) {
                $valor = $resueltos[$codigo] ?? null;

                Cache::put($this->cacheKeyUltimo($codigo), $valor, now()->addMinutes(self::CACHE_TTL_MINUTOS));

                $porCodigo[$codigo] = $valor;
            }
        }

        return array_values(array_filter(
            array_map(fn (string $codigo) => $porCodigo[$codigo] ?? null, $codigos),
        ));
    }

    /**
     * Invalida el último valor cacheado de un código. Se invoca cuando el
     * servicio de persistencia registra un nuevo valor para ese código.
     */
    public function invalidarUltimoPorTipo(string $codigo): void
    {
        Cache::forget($this->cacheKeyUltimo($codigo));
    }

    private function cacheKeyUltimo(string $codigo): string
    {
        return "indicadores_economicos:ultimo:{$codigo}";
    }

    /**
     * @param  list<string>  $codigos
     * @return array<string, array{codigo: string, valor: string, fecha_valor: ?string, periodo: ?string}>
     */
    private function resolverUltimosPorTipo(array $codigos): array
    {
        return IndicadorEconomico::whereIn('codigo', $codigos)
            ->orderByDesc('fecha_valor')
            ->orderByDesc('periodo')
            ->get()
            ->groupBy('codigo')
            ->map(function ($indicadores) {
                $indicador = $indicadores->first();
                $fechaValor = $indicador->fecha_valor;

                return [
                    'codigo' => $indicador->codigo,
                    'valor' => (string) $indicador->valor,
                    'fecha_valor' => $fechaValor === null ? null : Carbon::parse($fechaValor)->toDateString(),
                    'periodo' => $indicador->periodo,
                ];
            })
            ->all();
    }

    private function aplicarFallbackUsd(CarbonInterface $fecha): ?IndicadorEconomico
    {
        $estrategia = config('indicadores.usd_fallback');

        return match ($estrategia) {
            'ultimo_valor_disponible' => IndicadorEconomico::where('codigo', 'USD')
                ->whereDate('fecha_valor', '<=', $fecha->toDateString())
                ->orderByDesc('fecha_valor')
                ->first(),
            default => null,
        };
    }
}
