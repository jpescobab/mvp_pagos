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
