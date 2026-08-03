<?php

namespace App\Http\Resources\Contratos;

use App\Http\Resources\PagoProveedores\ProcesoResource;
use App\Models\Contrato;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Contrato */
class ContratoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_institucional' => $this->id_institucional,
            'codigo' => $this->codigo,
            'modalidad_compra' => $this->modalidad_compra,
            'id_proceso_mp' => $this->id_proceso_mp,
            'tipo_contrato' => $this->tipo_contrato,
            'referencia' => $this->referencia,
            'fecha_inicio_vigencia' => $this->fecha_inicio_vigencia,
            'fecha_fin_vigencia' => $this->fecha_fin_vigencia,
            'proveedor' => [
                'id' => $this->proveedor?->id,
                'nombre' => $this->proveedor?->nombre,
                'rutproveedor' => $this->proveedor?->rutproveedor,
            ],
            'materia' => $this->materia,
            'submateria' => $this->submateria,
            'tiene_convenio_precio' => $this->tiene_convenio_precio,
            'tiene_calendario_pago' => $this->tiene_calendario_pago,
            'periodicidad_pago' => $this->periodicidad_pago,
            'monto_total' => $this->monto_total,
            'proceso_adquisicion' => $this->whenLoaded(
                'procesoAdquisicion',
                fn () => $this->procesoAdquisicion === null ? null : [
                    'id' => $this->procesoAdquisicion->id,
                    'codigo' => $this->procesoAdquisicion->codigo,
                ],
            ),
            'licitacion_mercado_publico' => $this->whenLoaded(
                'licitacionMercadoPublico',
                fn () => $this->licitacionMercadoPublico === null ? null : [
                    'id' => $this->licitacionMercadoPublico->id,
                    'codigo' => $this->licitacionMercadoPublico->codigo,
                ],
            ),
            'proceso' => new ProcesoResource($this->proceso),
            'items_convenio_precio' => ContratoItemConvenioPrecioResource::collection(
                $this->whenLoaded('itemsConvenioPrecio'),
            ),
            'cuotas' => ContratoCuotaResource::collection($this->whenLoaded('cuotas')),
            'ordenes_compra_mercado_publico' => $this->whenLoaded(
                'ordenesCompraMercadoPublico',
                fn () => $this->ordenesCompraMercadoPublico->map(fn ($orden) => [
                    'id' => $orden->id,
                    'codigo' => $orden->codigo,
                ])->values(),
            ),
        ];
    }
}
