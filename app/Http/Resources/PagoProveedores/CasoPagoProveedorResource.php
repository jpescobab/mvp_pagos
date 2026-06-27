<?php

namespace App\Http\Resources\PagoProveedores;

use App\Models\CasoPagoProveedor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CasoPagoProveedor */
class CasoPagoProveedorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sgf_id' => $this->sgf_id,
            'proveedor' => [
                'nombre' => $this->proveedor?->nombre,
                'rutproveedor' => $this->proveedor?->rutproveedor,
            ],
            'monto' => $this->monto,
            'sgf_status' => $this->sgf_status,
            'sgf_current_group_raw' => $this->sgf_current_group_raw,
            'proceso' => new ProcesoResource($this->proceso),
        ];
    }
}
