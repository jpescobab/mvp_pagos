<?php

namespace App\Http\Resources\Reportabilidad;

use App\Models\CorteReportabilidad;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CorteReportabilidad */
class CorteReportabilidadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fecha_corte' => $this->fecha_corte,
            'estado' => $this->estado,
            'publicado_por' => $this->publicadoPor?->name,
            'publicado_en' => $this->publicado_en,
            'periodo' => $this->whenLoaded('periodoReportabilidad', fn () => [
                'id' => $this->periodoReportabilidad->id,
                'codigo' => $this->periodoReportabilidad->codigo,
            ]),
            'items_count' => $this->whenCounted('items'),
            'snapshots_count' => $this->whenCounted('snapshots'),
            'ejecuciones_informe_razonado_count' => $this->whenCounted('ejecucionesInformeRazonado'),
        ];
    }
}
