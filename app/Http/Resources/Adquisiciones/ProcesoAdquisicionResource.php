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
            'fecha_inicio' => $this->fecha_inicio,
            'nombre' => $this->nombre,
            'id_requerimiento' => $this->id_requerimiento,
            'modalidad' => [
                'codigo' => $this->modalidad?->codigo,
                'nombre' => $this->modalidad?->nombre,
            ],
            'ccosto' => [
                'codigo' => $this->ccosto?->codigo,
                'nombre' => $this->ccosto?->nombre,
            ],
            'funcionario_requirente' => [
                'id' => $this->funcionarioRequirente?->id,
                'nombre' => $this->funcionarioRequirente?->nombre,
                'cargo' => $this->funcionarioRequirente?->cargo,
            ],
            'proveedor' => [
                'nombre' => $this->proveedor?->nombre,
                'rutproveedor' => $this->proveedor?->rutproveedor,
            ],
            'caracteristicas' => $this->caracteristicas,
            'motivo_contratacion' => $this->motivo_contratacion,
            'en_plan_compras' => $this->en_plan_compras,
            'id_pac' => $this->id_pac,
            'codigo_bip' => $this->codigo_bip,
            'moneda_compra' => $this->moneda_compra,
            'monto_estimado_solicitado' => $this->monto_estimado_solicitado,
            'fecha_paridad' => $this->fecha_paridad,
            'paridad' => $this->paridad,
            'monto_estimado' => $this->monto_estimado,
            'proceso' => new ProcesoResource($this->proceso),
            'casos_pago_proveedor' => $this->whenLoaded(
                'casosPagoProveedor',
                fn () => $this->casosPagoProveedor->map(fn ($caso) => [
                    'id' => $caso->id,
                    'sgf_id' => $caso->sgf_id,
                ])->values(),
            ),
            'ordenes_compra_mercado_publico' => $this->whenLoaded(
                'ordenesCompraMercadoPublico',
                fn () => $this->ordenesCompraMercadoPublico->map(fn ($orden) => [
                    'id' => $orden->id,
                    'codigo' => $orden->codigo,
                    'estado_mercado_publico' => $orden->estado_mercado_publico,
                    'organismo' => $this->nombreOrganismo($orden->organismo_comprador),
                    'monto' => $orden->monto_total,
                ])->values(),
            ),
            'licitaciones_mercado_publico' => $this->whenLoaded(
                'licitacionesMercadoPublico',
                fn () => $this->licitacionesMercadoPublico->map(fn ($licitacion) => [
                    'id' => $licitacion->id,
                    'codigo' => $licitacion->codigo,
                    'nombre' => $licitacion->nombre,
                    'estado_mercado_publico' => $licitacion->estado_mercado_publico,
                    'organismo' => $this->nombreOrganismo($licitacion->organismo_comprador),
                    'monto' => $licitacion->monto_estimado,
                ])->values(),
            ),
        ];
    }

    /**
     * Extrae de forma defensiva el nombre del organismo comprador del payload
     * (array JSON de Mercado Público), sin asumir su estructura completa.
     *
     * @param  array<string, mixed>|null  $organismo
     */
    private function nombreOrganismo(?array $organismo): ?string
    {
        $nombre = $organismo['nombre'] ?? null;

        return is_string($nombre) ? $nombre : null;
    }
}
