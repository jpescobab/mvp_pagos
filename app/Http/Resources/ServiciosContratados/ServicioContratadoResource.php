<?php

namespace App\Http\Resources\ServiciosContratados;

use App\Models\ServicioContratado;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ServicioContratado */
class ServicioContratadoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $estado = $this->estadoDerivado();

        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'categoria' => $this->categoria,
            'monto_mensual_esperado' => $this->monto_mensual_esperado,
            'moneda' => $this->moneda,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            'observaciones' => $this->observaciones,
            'terminado_en' => $this->terminado_en,
            'estado' => $estado,
            'por_vencer' => $estado === ServicioContratado::ESTADO_POR_VENCER,
            'contrato' => [
                'id' => $this->contrato->id,
                'codigo' => $this->contrato->codigo,
                'id_institucional' => $this->contrato->id_institucional,
                'referencia' => $this->contrato->referencia,
                'fecha_inicio_vigencia' => $this->contrato->fecha_inicio_vigencia,
                'fecha_fin_vigencia' => $this->contrato->fecha_fin_vigencia,
            ],
            'proveedor' => [
                'id' => $this->contrato->proveedor?->id,
                'nombre' => $this->contrato->proveedor?->nombre,
                'rutproveedor' => $this->contrato->proveedor?->rutproveedor,
            ],
            'ccosto' => $this->ccosto === null ? null : [
                'id' => $this->ccosto->id,
                'codigo' => $this->ccosto->codigo,
                'nombre' => $this->ccosto->nombre,
            ],
            'administrador' => $this->administrador === null ? null : [
                'id' => $this->administrador->id,
                'nombre' => $this->administrador->nombre,
            ],
        ];
    }
}
