<?php

namespace App\Services\Indicadores;

use App\Models\IndicadorEconomico;
use App\Models\IndicadorEconomicoImportacion;
use App\Services\Cmf\CmfClient;
use Carbon\CarbonImmutable;

class ServicioImportacionIndicadores
{
    public function __construct(
        private readonly CmfClient $cmf,
        private readonly ServicioPersistenciaIndicadores $persistencia,
    ) {}

    /**
     * Import UF (tramo mensual vigente), UTM, IPC, and compute UTA when its
     * underlying UTM (diciembre) is already published.
     */
    public function importarMensual(
        string $tipoImportacion = 'mensual_indicadores',
        ?string $periodo = null,
        ?string $ejecutadoPorJob = null,
    ): IndicadorEconomicoImportacion {
        $hoy = $periodo !== null
            ? CarbonImmutable::createFromFormat('Y-m', $periodo)->startOfMonth()
            : CarbonImmutable::now();

        $registrador = new RegistradorImportacionIndicadores;

        $importacion = $registrador->iniciar([
            'tipo_importacion' => $periodo !== null ? 'reproceso_controlado' : $tipoImportacion,
            'indicadores_solicitados' => ['UF', 'UTM', 'UTA', 'IPC'],
            'fuente_principal' => 'CMF',
            'periodo' => $periodo,
            'ejecutado_por_job' => $ejecutadoPorJob,
        ]);

        try {
            $this->importarTramoUf($importacion, $registrador, $hoy);
        } catch (\Throwable $e) {
            $registrador->fallido("UF: {$e->getMessage()}");
        }

        try {
            $this->importarUtmDelAnio($importacion, $registrador, $hoy->year);
            $this->importarUtmDelAnio($importacion, $registrador, $hoy->year - 1);
            $this->importarUtaCalculada($importacion, $registrador, $hoy->year);
            $this->importarUtaCalculada($importacion, $registrador, $hoy->year - 1);
        } catch (\Throwable $e) {
            $registrador->fallido("UTM/UTA: {$e->getMessage()}");
        }

        try {
            $this->importarIpc($importacion, $registrador);
        } catch (\Throwable $e) {
            $registrador->fallido("IPC: {$e->getMessage()}");
        }

        return $registrador->finalizar($importacion);
    }

    /**
     * Import USD ("dólar observado") for the latest published date. Never
     * fabricates a value for today if the CMF has none published yet — it
     * only records a warning. Fallback for missing dates is a read-time
     * concern, handled by IndicadorEconomicoSelector.
     */
    public function importarUsd(
        string $tipoImportacion = 'diaria_usd',
        ?string $fecha = null,
        ?string $ejecutadoPorJob = null,
    ): IndicadorEconomicoImportacion {
        $hoy = $fecha !== null
            ? CarbonImmutable::parse($fecha)->startOfDay()
            : CarbonImmutable::now()->startOfDay();

        $registrador = new RegistradorImportacionIndicadores;

        $importacion = $registrador->iniciar([
            'tipo_importacion' => $fecha !== null ? 'reproceso_controlado' : $tipoImportacion,
            'indicadores_solicitados' => ['USD'],
            'fuente_principal' => 'CMF',
            'fecha_desde' => $hoy->toDateString(),
            'fecha_hasta' => $hoy->toDateString(),
            'ejecutado_por_job' => $ejecutadoPorJob,
        ]);

        try {
            $registrador->recibido();
            $respuesta = $this->cmf->dolar();
            $entrada = $respuesta['data'][0] ?? null;

            if ($entrada === null) {
                $registrador->fallido('La CMF no devolvió ningún valor de USD.');

                return $registrador->finalizar($importacion);
            }

            $fechaValor = CarbonImmutable::parse($entrada['fecha']);

            if (! $fechaValor->isSameDay($hoy)) {
                $registrador->advertir("La CMF reportó USD para {$fechaValor->toDateString()} en vez de {$hoy->toDateString()} (día inhábil o sin publicación).");
            }

            $resultado = $this->persistencia->crearSiNoExiste([
                'importacion_id' => $importacion->id,
                'codigo' => 'USD',
                'nombre' => 'Dólar observado',
                'tipo' => 'moneda',
                'fecha_valor' => $fechaValor->toDateString(),
                'valor' => $entrada['valor'],
                'periodicidad_valor' => 'diaria',
                'periodicidad_publicacion' => 'diaria_habil',
                'unidad_medida' => 'CLP',
                'moneda_base' => 'USD',
                'fuente' => 'CMF',
                'endpoint' => $respuesta['url'],
                'source_url' => $respuesta['url'],
                'source_payload' => $respuesta['raw'],
                'capturado_en' => now(),
                'capturado_por_job' => $ejecutadoPorJob,
                'requiere_dia_habil' => true,
            ]);

            $resultado['creado'] ? $registrador->creado() : $registrador->omitido();
        } catch (\Throwable $e) {
            $registrador->fallido($e->getMessage());
        }

        return $registrador->finalizar($importacion);
    }

    private function importarTramoUf(
        IndicadorEconomicoImportacion $importacion,
        RegistradorImportacionIndicadores $registrador,
        CarbonImmutable $hoy,
    ): void {
        // El tramo vigente de la CMF va del día 10 de un mes al día 9 del
        // siguiente. Si "hoy" cae antes del día 10 del mes calendario, el
        // tramo vigente empezó el día 10 del mes ANTERIOR (el de este mes
        // recién se publica cerca del día 9-10 y todavía no existe).
        $inicio = ($hoy->day >= 10 ? $hoy : $hoy->subMonthNoOverflow())
            ->setDay(10)
            ->startOfDay();
        $fin = $inicio->addMonthNoOverflow()->subDay();

        $respuestaInicio = $this->cmf->uf($inicio->year, $inicio->month);
        $respuestaFin = $this->cmf->uf($fin->year, $fin->month);

        $valores = collect([...$respuestaInicio['data'], ...$respuestaFin['data']])
            ->filter(function (array $valor) use ($inicio, $fin) {
                $fecha = CarbonImmutable::parse($valor['fecha']);

                return $fecha->between($inicio, $fin);
            });

        foreach ($valores as $valor) {
            $registrador->recibido();

            $resultado = $this->persistencia->crearSiNoExiste([
                'importacion_id' => $importacion->id,
                'codigo' => 'UF',
                'nombre' => 'Unidad de Fomento',
                'tipo' => 'unidad_reajustable',
                'fecha_valor' => $valor['fecha'],
                'valor' => $valor['valor'],
                'periodicidad_valor' => 'diaria',
                'periodicidad_publicacion' => 'tramo_mensual',
                'vigente_desde' => $inicio->toDateString(),
                'vigente_hasta' => $fin->toDateString(),
                'unidad_medida' => 'CLP',
                'moneda_base' => 'CLP',
                'fuente' => 'CMF',
                'endpoint' => $respuestaInicio['url'],
                'source_url' => $respuestaInicio['url'],
                'source_payload' => ['inicio' => $respuestaInicio['raw'], 'fin' => $respuestaFin['raw']],
                'capturado_en' => now(),
                'capturado_por_job' => $importacion->ejecutado_por_job,
            ]);

            $resultado['creado'] ? $registrador->creado() : $registrador->omitido();
        }
    }

    private function importarUtmDelAnio(
        IndicadorEconomicoImportacion $importacion,
        RegistradorImportacionIndicadores $registrador,
        int $anio,
    ): void {
        $respuesta = $this->cmf->utm($anio);

        foreach ($respuesta['data'] as $valor) {
            $registrador->recibido();

            $resultado = $this->persistencia->crearSiNoExiste([
                'importacion_id' => $importacion->id,
                'codigo' => 'UTM',
                'nombre' => 'Unidad Tributaria Mensual',
                'tipo' => 'unidad_tributaria',
                'periodo' => CarbonImmutable::parse($valor['fecha'])->format('Y-m'),
                'valor' => $valor['valor'],
                'periodicidad_valor' => 'mensual',
                'periodicidad_publicacion' => 'mensual',
                'unidad_medida' => 'CLP',
                'moneda_base' => 'CLP',
                'fuente' => 'CMF',
                'endpoint' => $respuesta['url'],
                'source_url' => $respuesta['url'],
                'capturado_en' => now(),
                'capturado_por_job' => $importacion->ejecutado_por_job,
            ]);

            $resultado['creado'] ? $registrador->creado() : $registrador->omitido();
        }
    }

    /**
     * UTA is not a CMF endpoint (confirmed HTTP 302). It is the UTM of
     * December of the given "año comercial", multiplied by 12 — the
     * standard SII definition.
     */
    private function importarUtaCalculada(
        IndicadorEconomicoImportacion $importacion,
        RegistradorImportacionIndicadores $registrador,
        int $anioComercial,
    ): void {
        $periodo = (string) $anioComercial;

        if (IndicadorEconomico::where('codigo', 'UTA')->where('periodo', $periodo)->exists()) {
            return;
        }

        $utmDiciembre = IndicadorEconomico::where('codigo', 'UTM')
            ->where('periodo', sprintf('%d-12', $anioComercial))
            ->first();

        if ($utmDiciembre === null) {
            return;
        }

        $registrador->recibido();

        $resultado = $this->persistencia->crearSiNoExiste([
            'importacion_id' => $importacion->id,
            'codigo' => 'UTA',
            'nombre' => 'Unidad Tributaria Anual',
            'tipo' => 'unidad_tributaria',
            'periodo' => $periodo,
            'valor' => $utmDiciembre->valor * 12,
            'periodicidad_valor' => 'anual',
            'periodicidad_publicacion' => 'anual',
            'unidad_medida' => 'CLP',
            'moneda_base' => 'CLP',
            'fuente' => 'calculado_utm',
            'source_payload' => ['utm_indicador_id' => $utmDiciembre->id, 'utm_periodo' => $utmDiciembre->periodo],
            'capturado_en' => now(),
            'capturado_por_job' => $importacion->ejecutado_por_job,
        ]);

        $resultado['creado'] ? $registrador->creado() : $registrador->omitido();
    }

    private function importarIpc(
        IndicadorEconomicoImportacion $importacion,
        RegistradorImportacionIndicadores $registrador,
    ): void {
        $respuesta = $this->cmf->ipc();

        foreach ($respuesta['data'] as $valor) {
            $registrador->recibido();

            $resultado = $this->persistencia->crearSiNoExiste([
                'importacion_id' => $importacion->id,
                'codigo' => 'IPC',
                'nombre' => 'Índice de Precios al Consumidor',
                'tipo' => 'indice',
                'periodo' => CarbonImmutable::parse($valor['fecha'])->format('Y-m'),
                'valor' => $valor['valor'],
                'periodicidad_valor' => 'mensual',
                'periodicidad_publicacion' => 'mensual',
                'unidad_medida' => 'porcentaje',
                'moneda_base' => 'CLP',
                'fuente' => 'CMF',
                'endpoint' => $respuesta['url'],
                'source_url' => $respuesta['url'],
                'capturado_en' => now(),
                'capturado_por_job' => $importacion->ejecutado_por_job,
            ]);

            $resultado['creado'] ? $registrador->creado() : $registrador->omitido();
        }
    }
}
