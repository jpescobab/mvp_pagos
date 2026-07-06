<?php

namespace App\Http\Resources\Maestros;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Item */
class ItemResource extends JsonResource
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
            'asignaciones' => AsignacionResource::collection($this->whenLoaded('asignaciones')),
            'catalogos' => CatalogoResource::collection($this->whenLoaded('catalogos')),
        ];
    }
}
