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
     * Memo de instancia: códigos ya resueltos (desde caché o BD) durante
     * este request. El selector se registra como singleton (ver
     * AppServiceProvider), así que esta propiedad vive mientras dura el
     * request HTTP y evita que dos callers distintos (HandleInertiaRequests
     * y DashboardController, típicamente) repitan operaciones de caché para
     * los códigos que se solapan entre ambos.
     *
     * @var array<string, array{codigo: string, valor: string, fecha_valor: ?string, periodo: ?string}|null>
     */
    private array $resueltosEnRequest = [];

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
     * códigos sin entrada vigente se resuelven en una sola consulta. Dentro
     * de un mismo request, un código ya resuelto por una llamada anterior
     * sobre esta misma instancia no vuelve a tocar ni el store de caché ni
     * la base de datos.
     *
     * @param  list<string>  $codigos
     * @return list<array{codigo: string, valor: string, fecha_valor: ?string, periodo: ?string}>
     */
    public function ultimosPorTipo(array $codigos): array
    {
        $porCodigo = [];
        $codigosSinMemo = [];

        foreach ($codigos as $codigo) {
            if (array_key_exists($codigo, $this->resueltosEnRequest)) {
                $porCodigo[$codigo] = $this->resueltosEnRequest[$codigo];

                continue;
            }

            $codigosSinMemo[] = $codigo;
        }

        if ($codigosSinMemo !== []) {
            $claves = [];

            foreach ($codigosSinMemo as $codigo) {
                $claves[$this->cacheKeyUltimo($codigo)] = self::CACHE_MISS;
            }

            $cacheados = Cache::many($claves);
            $faltantes = [];

            foreach ($codigosSinMemo as $codigo) {
                $valor = $cacheados[$this->cacheKeyUltimo($codigo)];

                if ($valor === self::CACHE_MISS) {
                    $faltantes[] = $codigo;

                    continue;
                }

                $porCodigo[$codigo] = $valor;
                $this->resueltosEnRequest[$codigo] = $valor;
            }

            if ($faltantes !== []) {
                $resueltos = $this->resolverUltimosPorTipo($faltantes);
                $paraCachear = [];

                foreach ($faltantes as $codigo) {
                    $valor = $resueltos[$codigo] ?? null;

                    $paraCachear[$this->cacheKeyUltimo($codigo)] = $valor;
                    $porCodigo[$codigo] = $valor;
                    $this->resueltosEnRequest[$codigo] = $valor;
                }

                Cache::putMany($paraCachear, now()->addMinutes(self::CACHE_TTL_MINUTOS));
            }
        }

        return array_values(array_filter(
            array_map(fn (string $codigo) => $porCodigo[$codigo] ?? null, $codigos),
        ));
    }

    /**
     * Invalida el último valor cacheado de un código, tanto en el memo de
     * instancia como en el store de caché. Se invoca cuando el servicio de
     * persistencia registra un nuevo valor para ese código.
     */
    public function invalidarUltimoPorTipo(string $codigo): void
    {
        unset($this->resueltosEnRequest[$codigo]);

        Cache::forget($this->cacheKeyUltimo($codigo));
    }

    private function cacheKeyUltimo(string $codigo): string
    {
        return "indicadores_economicos:ultimo:{$codigo}";
    }

    /**
     * Trae, en una sola consulta, únicamente la fila más reciente de cada
     * código solicitado (no todas las filas que matcheen) mediante una
     * función de ventana particionada por código. Portable entre PostgreSQL
     * y SQLite (no usa DISTINCT ON, exclusivo de Postgres).
     *
     * @param  list<string>  $codigos
     * @return array<string, array{codigo: string, valor: string, fecha_valor: ?string, periodo: ?string}>
     */
    private function resolverUltimosPorTipo(array $codigos): array
    {
        $conRn = IndicadorEconomico::query()
            ->select('*')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY codigo ORDER BY fecha_valor DESC, periodo DESC) AS rn')
            ->whereIn('codigo', $codigos);

        return IndicadorEconomico::query()
            ->fromSub($conRn, 'indicadores_economicos')
            ->where('rn', 1)
            ->get()
            ->keyBy('codigo')
            ->map(function (IndicadorEconomico $indicador): array {
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
