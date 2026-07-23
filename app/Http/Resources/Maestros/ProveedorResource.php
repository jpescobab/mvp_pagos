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
            'estado' => $this->estado,
            'giro' => $this->giro,
            'tipo_contribuyente' => $this->tipo_contribuyente,
            'rubros' => $this->rubros,
            'contacto_cargo' => $this->contacto_cargo,
            'contacto_telefono' => $this->contacto_telefono,
            'region' => $this->region,
            'comuna' => $this->comuna,
            'banco' => $this->banco,
            'tipo_cuenta' => $this->tipo_cuenta,
            'numero_cuenta' => $this->numero_cuenta,
            'condicion_pago' => $this->condicion_pago,
            'moneda' => $this->moneda,
            'correo_pago' => $this->correo_pago,
            'notas_internas' => $this->notas_internas,
        ];
    }
}
