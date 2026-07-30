<?php

namespace App\Http\Resources\Presupuesto;

use App\Models\Presupuesto\ImportacionPresupuesto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/** @mixin ImportacionPresupuesto */
class ImportacionPresupuestoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Carbon|null $iniciadoEn */
        $iniciadoEn = $this->iniciado_en;
        /** @var Carbon|null $finalizadoEn */
        $finalizadoEn = $this->finalizado_en;

        return [
            'id' => $this->id,
            'nro_version' => $this->nro_version,
            'anio' => $this->anio,
            'estado' => $this->estado,
            'total_recibidos' => $this->total_recibidos,
            'total_creados' => $this->total_creados,
            'total_actualizados' => $this->total_actualizados,
            'total_omitidos' => $this->total_omitidos,
            'total_fallidos' => $this->total_fallidos,
            'advertencias' => $this->advertencias,
            'iniciado_en' => $iniciadoEn?->toIso8601String(),
            'finalizado_en' => $finalizadoEn?->toIso8601String(),
            'creado_por' => $this->whenLoaded('creadoPor', fn () => $this->creadoPor?->name),
        ];
    }
}
