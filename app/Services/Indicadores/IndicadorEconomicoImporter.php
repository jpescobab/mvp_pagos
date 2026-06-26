<?php

namespace App\Services\Indicadores;

use App\Models\IndicadorEconomico;
use App\Models\IndicadorEconomicoImportacion;
use App\Services\Cmf\CmfClient;
use Carbon\CarbonImmutable;

class IndicadorEconomicoImporter
{
    public function __construct(private readonly CmfClient $cmf) {}

    /**
     * Import UF (tramo mensual vigente), UTM, IPC, and compute UTA when its
     * underlying UTM (diciembre) is already published.
     */
    public function importarMensual(): IndicadorEconomicoImportacion
    {
        $hoy = CarbonImmutable::now();
        $errores = [];

        $importacion = IndicadorEconomicoImportacion::create([
            'tipo' => 'mensual',
            'estado' => 'ok',
        ]);

        try {
            $this->importarTramoUf($importacion, $hoy);
        } catch (\Throwable $e) {
            $errores[] = "UF: {$e->getMessage()}";
        }

        try {
            $this->importarUtmDelAnio($importacion, $hoy->year);
            $this->importarUtmDelAnio($importacion, $hoy->year - 1);
            $this->importarUtaCalculada($importacion, $hoy->year);
            $this->importarUtaCalculada($importacion, $hoy->year - 1);
        } catch (\Throwable $e) {
            $errores[] = "UTM/UTA: {$e->getMessage()}";
        }

        try {
            $this->importarIpc($importacion);
        } catch (\Throwable $e) {
            $errores[] = "IPC: {$e->getMessage()}";
        }

        $importacion->update([
            'estado' => $errores === [] ? 'ok' : 'error',
            'errores' => $errores === [] ? null : $errores,
        ]);

        return $importacion->refresh();
    }

    /**
     * Import USD ("dólar observado") for the latest published date. Never
     * fabricates a value for today if the CMF has none published yet — it
     * only records a warning. Fallback for missing dates is a read-time
     * concern, handled by IndicadorEconomicoSelector.
     */
    public function importarDolarDiario(): IndicadorEconomicoImportacion
    {
        $hoy = CarbonImmutable::now()->startOfDay();

        $importacion = IndicadorEconomicoImportacion::create([
            'tipo' => 'diario',
            'estado' => 'ok',
        ]);

        try {
            $respuesta = $this->cmf->dolar();
            $importacion->update(['endpoint' => $respuesta['url'], 'source_payload' => $respuesta['raw']]);

            $entrada = $respuesta['data'][0] ?? null;

            if ($entrada === null) {
                $importacion->update(['estado' => 'error', 'errores' => ['La CMF no devolvió ningún valor de USD.']]);

                return $importacion->refresh();
            }

            $fechaValor = CarbonImmutable::parse($entrada['fecha']);
            $advertencias = [];

            if (! $fechaValor->isSameDay($hoy)) {
                $advertencias[] = "La CMF reportó USD para {$fechaValor->toDateString()} en vez de {$hoy->toDateString()} (día inhábil o sin publicación).";
            }

            $this->crearIndicador($importacion, [
                'tipo' => 'USD',
                'fecha_valor' => $fechaValor->toDateString(),
                'valor' => $entrada['valor'],
                'periodicidad_valor' => 'diaria',
                'fuente' => 'CMF',
                'source_url' => $respuesta['url'],
                'advertencias' => $advertencias === [] ? null : $advertencias,
            ]);

            $importacion->update([
                'estado' => $advertencias === [] ? 'ok' : 'con_advertencias',
                'advertencias' => $advertencias === [] ? null : $advertencias,
            ]);
        } catch (\Throwable $e) {
            $importacion->update(['estado' => 'error', 'errores' => [$e->getMessage()]]);
        }

        return $importacion->refresh();
    }

    private function importarTramoUf(IndicadorEconomicoImportacion $importacion, CarbonImmutable $hoy): void
    {
        $inicio = $hoy->setDay(10)->startOfDay();
        $fin = $inicio->addMonthNoOverflow()->subDay();

        $respuestaInicio = $this->cmf->uf($inicio->year, $inicio->month);
        $respuestaFin = $this->cmf->uf($fin->year, $fin->month);

        $importacion->update([
            'endpoint' => $respuestaInicio['url'],
            'source_payload' => ['inicio' => $respuestaInicio['raw'], 'fin' => $respuestaFin['raw']],
        ]);

        $valores = collect([...$respuestaInicio['data'], ...$respuestaFin['data']])
            ->filter(function (array $valor) use ($inicio, $fin) {
                $fecha = CarbonImmutable::parse($valor['fecha']);

                return $fecha->between($inicio, $fin);
            });

        foreach ($valores as $valor) {
            $this->crearIndicador($importacion, [
                'tipo' => 'UF',
                'fecha_valor' => $valor['fecha'],
                'valor' => $valor['valor'],
                'periodicidad_valor' => 'diaria',
                'periodicidad_publicacion' => 'tramo_mensual',
                'vigente_desde' => $inicio->toDateString(),
                'vigente_hasta' => $fin->toDateString(),
                'fuente' => 'CMF',
                'source_url' => $respuestaInicio['url'],
            ]);
        }
    }

    private function importarUtmDelAnio(IndicadorEconomicoImportacion $importacion, int $anio): void
    {
        $respuesta = $this->cmf->utm($anio);

        $importacion->update(['endpoint' => $respuesta['url']]);

        foreach ($respuesta['data'] as $valor) {
            $this->crearIndicador($importacion, [
                'tipo' => 'UTM',
                'periodo' => CarbonImmutable::parse($valor['fecha'])->format('Y-m'),
                'valor' => $valor['valor'],
                'periodicidad_valor' => 'mensual',
                'fuente' => 'CMF',
                'source_url' => $respuesta['url'],
            ]);
        }
    }

    /**
     * UTA is not a CMF endpoint (confirmed HTTP 302). It is the UTM of
     * December of the given "año comercial", multiplied by 12 — the
     * standard SII definition.
     */
    private function importarUtaCalculada(IndicadorEconomicoImportacion $importacion, int $anioComercial): void
    {
        $periodo = (string) $anioComercial;

        if (IndicadorEconomico::where('tipo', 'UTA')->where('periodo', $periodo)->exists()) {
            return;
        }

        $utmDiciembre = IndicadorEconomico::where('tipo', 'UTM')
            ->where('periodo', sprintf('%d-12', $anioComercial))
            ->first();

        if ($utmDiciembre === null) {
            return;
        }

        $this->crearIndicador($importacion, [
            'tipo' => 'UTA',
            'periodo' => $periodo,
            'valor' => $utmDiciembre->valor * 12,
            'periodicidad_valor' => 'anual',
            'fuente' => 'calculado_utm',
            'source_payload' => ['utm_indicador_id' => $utmDiciembre->id, 'utm_periodo' => $utmDiciembre->periodo],
        ]);
    }

    private function importarIpc(IndicadorEconomicoImportacion $importacion): void
    {
        $respuesta = $this->cmf->ipc();

        $importacion->update(['endpoint' => $respuesta['url']]);

        foreach ($respuesta['data'] as $valor) {
            $this->crearIndicador($importacion, [
                'tipo' => 'IPC',
                'periodo' => CarbonImmutable::parse($valor['fecha'])->format('Y-m'),
                'valor' => $valor['valor'],
                'periodicidad_valor' => 'mensual',
                'fuente' => 'CMF',
                'source_url' => $respuesta['url'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function crearIndicador(IndicadorEconomicoImportacion $importacion, array $atributos): IndicadorEconomico
    {
        $llave = array_intersect_key($atributos, array_flip(['tipo', 'fecha_valor', 'periodo']));

        return IndicadorEconomico::firstOrCreate(
            $llave,
            [...$atributos, 'importacion_id' => $importacion->id],
        );
    }
}
