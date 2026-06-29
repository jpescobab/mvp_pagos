<?php

namespace App\Http\Resources\Maestros;

use App\Models\ClienteMedidor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ClienteMedidor */
class ClienteMedidorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_cliente' => $this->numero_cliente,
            'proveedor' => $this->proveedor === null ? null : [
                'nombre' => $this->proveedor->nombre,
                'rutproveedor' => $this->proveedor->rutproveedor,
            ],
            'ccosto' => [
                'codigo' => $this->ccosto->codigo,
                'nombre' => $this->ccosto->nombre,
            ],
            'tipo_suministro' => $this->tipo_suministro,
            'direccion_suministro' => $this->direccion_suministro,
            'activo' => $this->activo,
        ];
    }
}
