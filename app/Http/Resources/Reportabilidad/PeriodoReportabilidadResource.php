<?php

namespace App\Http\Resources\Reportabilidad;

use App\Models\PeriodoReportabilidad;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PeriodoReportabilidad */
class PeriodoReportabilidadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            'estado' => $this->estado,
            'cortes_count' => $this->whenCounted('cortesReportabilidad'),
            'cortes' => CorteReportabilidadResource::collection($this->whenLoaded('cortesReportabilidad')),
        ];
    }
}
