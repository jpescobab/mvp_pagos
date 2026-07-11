<?php

namespace App\Http\Resources\PagoProveedores;

use App\Models\TransicionWorkflow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TransicionWorkflow */
class TransicionWorkflowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'requiere_comentario' => $this->requiere_comentario,
            'permiso_requerido' => $this->permiso_requerido,
        ];
    }
}
