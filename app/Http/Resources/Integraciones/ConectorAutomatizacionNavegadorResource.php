<?php

namespace App\Http\Resources\Integraciones;

use App\Models\ConectorAutomatizacionNavegador;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ConectorAutomatizacionNavegador */
class ConectorAutomatizacionNavegadorResource extends JsonResource
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
            'esta_autorizado' => $this->estaAutorizado(),
            'autorizado_por' => $this->autorizadoPor?->name,
            'autorizado_en' => $this->autorizado_en,
            'sistema_externo' => $this->whenLoaded('sistemaExterno', fn () => [
                'codigo' => $this->sistemaExterno->codigo,
                'nombre' => $this->sistemaExterno->nombre,
            ]),
            'perfiles' => PerfilAutenticacionNavegadorResource::collection(
                $this->whenLoaded('perfilesAutenticacionNavegador'),
            ),
        ];
    }
}
