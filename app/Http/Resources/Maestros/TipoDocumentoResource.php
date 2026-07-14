<?php

namespace App\Http\Resources\Maestros;

use App\Models\TipoDocumento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TipoDocumento */
class TipoDocumentoResource extends JsonResource
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
            'es_obligatorio' => $this->es_obligatorio,
            'activo' => $this->activo,
        ];
    }
}
