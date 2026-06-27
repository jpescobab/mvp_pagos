<?php

namespace App\Http\Resources\Adquisiciones;

use App\Http\Resources\PagoProveedores\ProcesoResource;
use App\Models\ProcesoAdquisicion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProcesoAdquisicion */
class ProcesoAdquisicionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'modalidad' => [
                'codigo' => $this->modalidad?->codigo,
                'nombre' => $this->modalidad?->nombre,
            ],
            'ccosto' => [
                'codigo' => $this->ccosto?->codigo,
                'nombre' => $this->ccosto?->nombre,
            ],
            'proveedor' => [
                'nombre' => $this->proveedor?->nombre,
                'rutproveedor' => $this->proveedor?->rutproveedor,
            ],
            'monto' => $this->monto,
            'objeto' => $this->objeto,
            'proceso' => new ProcesoResource($this->proceso),
        ];
    }
}
