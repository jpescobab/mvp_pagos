<?php

namespace App\Http\Resources\InformesRazonados;

use App\Http\Resources\PagoProveedores\ProcesoResource;
use App\Models\AprobacionInformeRazonado;
use App\Models\EjecucionInformeRazonado;
use App\Models\ExcepcionInformeRazonado;
use App\Models\ExportacionInformeRazonado;
use App\Models\GraficoInformeRazonado;
use App\Models\MetricaInformeRazonado;
use App\Models\NarrativaInformeRazonado;
use App\Models\SeccionInformeRazonado;
use App\Models\SnapshotInformeRazonado;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EjecucionInformeRazonado */
class EjecucionInformeRazonadoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'definicion' => $this->whenLoaded('definicionInformeRazonado', fn () => [
                'id' => $this->definicionInformeRazonado->id,
                'codigo' => $this->definicionInformeRazonado->codigo,
                'nombre' => $this->definicionInformeRazonado->nombre,
            ]),
            'corte' => $this->whenLoaded('corteReportabilidad', fn () => [
                'id' => $this->corteReportabilidad->id,
                'estado' => $this->corteReportabilidad->estado,
                'periodo_codigo' => $this->corteReportabilidad->periodoReportabilidad?->codigo,
            ]),
            'generado_por' => $this->generadoPor?->name,
            'generado_en' => $this->generado_en,
            'proceso' => $this->whenLoaded('proceso', fn () => new ProcesoResource($this->proceso)),
            'secciones' => $this->whenLoaded('secciones', fn () => $this->mapSecciones()),
            'metricas' => $this->whenLoaded('metricas', fn () => $this->mapMetricas()),
            'graficos' => $this->whenLoaded('graficos', fn () => $this->mapGraficos()),
            'narrativas' => $this->whenLoaded('narrativas', fn () => $this->mapNarrativas()),
            'excepciones' => $this->whenLoaded('excepciones', fn () => $this->mapExcepciones()),
            'snapshots' => $this->whenLoaded('snapshots', fn () => $this->mapSnapshots()),
            'aprobaciones' => $this->whenLoaded('aprobaciones', fn () => $this->mapAprobaciones()),
            'exportaciones' => $this->whenLoaded('exportaciones', fn () => $this->mapExportaciones()),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapSecciones(): array
    {
        return array_values($this->secciones
            ->map(fn (SeccionInformeRazonado $seccion) => [
                'id' => $seccion->id,
                'codigo' => $seccion->codigo,
                'titulo' => $seccion->titulo,
                'orden' => $seccion->orden,
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapMetricas(): array
    {
        return array_values($this->metricas
            ->map(fn (MetricaInformeRazonado $metrica) => [
                'id' => $metrica->id,
                'codigo' => $metrica->codigo,
                'etiqueta' => $metrica->etiqueta,
                'valor' => $metrica->valor,
                'unidad' => $metrica->unidad,
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapGraficos(): array
    {
        return array_values($this->graficos
            ->map(fn (GraficoInformeRazonado $grafico) => [
                'id' => $grafico->id,
                'codigo' => $grafico->codigo,
                'titulo' => $grafico->titulo,
                'tipo' => $grafico->tipo,
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapNarrativas(): array
    {
        return array_values($this->narrativas
            ->map(fn (NarrativaInformeRazonado $narrativa) => [
                'id' => $narrativa->id,
                'contenido' => $narrativa->contenido,
                'generado_por_ia' => $narrativa->generado_por_ia,
                'revisado_en' => $narrativa->revisado_en,
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapExcepciones(): array
    {
        return array_values($this->excepciones
            ->map(fn (ExcepcionInformeRazonado $excepcion) => [
                'id' => $excepcion->id,
                'codigo' => $excepcion->codigo,
                'descripcion' => $excepcion->descripcion,
                'severidad' => $excepcion->severidad,
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapSnapshots(): array
    {
        return array_values($this->snapshots
            ->map(fn (SnapshotInformeRazonado $snapshot) => [
                'id' => $snapshot->id,
                'hash' => $snapshot->hash,
                'capturado_en' => $snapshot->capturado_en,
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapAprobaciones(): array
    {
        return array_values($this->aprobaciones
            ->map(fn (AprobacionInformeRazonado $aprobacion) => [
                'id' => $aprobacion->id,
                'decision' => $aprobacion->decision,
                'comentario' => $aprobacion->comentario,
                'aprobado_por' => $aprobacion->aprobadoPor?->name,
                'decidido_en' => $aprobacion->decidido_en,
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapExportaciones(): array
    {
        return array_values($this->exportaciones
            ->map(fn (ExportacionInformeRazonado $exportacion) => [
                'id' => $exportacion->id,
                'formato' => $exportacion->formato,
                'generado_en' => $exportacion->generado_en,
            ])
            ->all());
    }
}
