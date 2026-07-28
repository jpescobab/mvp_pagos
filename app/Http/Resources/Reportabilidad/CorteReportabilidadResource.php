<?php

namespace App\Http\Resources\Reportabilidad;

use App\Models\CorteReportabilidad;
use App\Models\CorteReportabilidadItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

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
            'items' => $this->whenLoaded('items', fn () => $this->mapItems()),
            'items_count' => $this->whenCounted('items'),
            'snapshots_count' => $this->whenCounted('snapshots'),
            'ejecuciones_informe_razonado_count' => $this->whenCounted('ejecucionesInformeRazonado'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapItems(): array
    {
        return array_values($this->items
            ->map(fn (CorteReportabilidadItem $item) => [
                'id' => $item->id,
                'etiqueta' => $item->etiqueta,
                'entidad_tipo' => $item->vinculable_type ? Str::headline(class_basename($item->vinculable_type)) : null,
                'entidad_id' => $item->vinculable_id,
            ])
            ->all());
    }
}
