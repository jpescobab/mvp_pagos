<?php

namespace App\Http\Resources\Integraciones;

use App\Models\PerfilAutenticacionNavegador;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PerfilAutenticacionNavegador */
class PerfilAutenticacionNavegadorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'almacen_secreto' => $this->almacen_secreto,
            'referencia_secreto' => $this->referencia_secreto,
            'activo' => $this->activo,
            'creado_por' => $this->creadoPor?->name,
        ];
    }
}
