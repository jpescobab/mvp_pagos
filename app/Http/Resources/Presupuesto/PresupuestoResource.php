<?php

namespace App\Http\Resources\Presupuesto;

use App\Models\Presupuesto\Presupuesto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Presupuesto */
class PresupuestoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'anio' => $this->anio,
            'monto_asignado' => $this->monto_asignado,
            'cfinanciero' => [
                'id' => $this->cfinanciero->id,
                'codigo' => $this->cfinanciero->codigo,
                'nombre' => $this->cfinanciero->nombre,
            ],
            'catalogo' => [
                'id' => $this->catalogo->id,
                'codigo' => $this->catalogo->codigo,
                'nombre' => $this->catalogo->nombre,
            ],
            'plan_tarea' => [
                'id' => $this->planTarea->id,
                'codigo' => $this->planTarea->codigo,
                'nombre' => $this->planTarea->nombre,
            ],
        ];
    }
}
