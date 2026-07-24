<?php

namespace App\Http\Resources\InformesRazonados;

use App\Models\DefinicionInformeRazonado;
use App\Models\EjecucionInformeRazonado;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DefinicionInformeRazonado */
class DefinicionInformeRazonadoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'activo' => $this->activo,
            'ejecuciones_count' => $this->whenCounted('ejecuciones'),
            'ejecuciones' => $this->whenLoaded('ejecuciones', fn () => $this->mapEjecuciones()),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapEjecuciones(): array
    {
        return array_values($this->ejecuciones
            ->map(fn (EjecucionInformeRazonado $ejecucion) => [
                'id' => $ejecucion->id,
                'generado_en' => $ejecucion->generado_en,
                'corte_fecha' => $ejecucion->corteReportabilidad?->fecha_corte,
                'periodo_codigo' => $ejecucion->corteReportabilidad?->periodoReportabilidad?->codigo,
                'estado' => $ejecucion->proceso?->estadoActual?->nombre,
            ])
            ->all());
    }
}
