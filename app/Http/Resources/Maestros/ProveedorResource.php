<?php

namespace App\Http\Resources\Maestros;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Proveedor */
class ProveedorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rutproveedor' => $this->rutproveedor,
            'nombre' => $this->nombre,
            'correo' => $this->correo,
            'direccion' => $this->direccion,
            'contacto' => $this->contacto,
            'activo' => $this->activo,
        ];
    }
}
