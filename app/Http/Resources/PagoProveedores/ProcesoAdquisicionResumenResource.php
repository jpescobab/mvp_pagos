<?php

namespace App\Http\Resources\PagoProveedores;

use App\Models\ProcesoAdquisicion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProcesoAdquisicion */
class ProcesoAdquisicionResumenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'objeto' => $this->objeto,
            'proveedor' => $this->proveedor?->nombre,
            'monto' => $this->monto,
        ];
    }
}
