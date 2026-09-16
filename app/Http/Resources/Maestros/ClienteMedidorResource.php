<?php

namespace App\Http\Resources\Maestros;

use App\Models\ClienteMedidor;
use App\Models\ConsumoBasico;
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
                'id' => $this->proveedor->id,
                'nombre' => $this->proveedor->nombre,
                'rutproveedor' => $this->proveedor->rutproveedor,
            ],
            'ccosto' => [
                'id' => $this->ccosto->id,
                'codigo' => $this->ccosto->codigo,
                'nombre' => $this->ccosto->nombre,
            ],
            'tipo_suministro' => $this->tipo_suministro,
            'direccion_suministro' => $this->direccion_suministro,
            'activo' => $this->activo,
            'consumos' => $this->whenLoaded(
                'consumos',
                fn () => $this->mapConsumos(),
            ),
        ];
    }

    /**
     * @return list<array{id: int, numero_documento: string, fecha_inicio_lectura: string, fecha_fin_lectura: string, consumo: float|null, lectura_estimada: bool, monto_total: float}>
     */
    private function mapConsumos(): array
    {
        return array_values($this->consumos
            ->map(fn (ConsumoBasico $consumo) => [
                'id' => $consumo->id,
                'numero_documento' => $consumo->numero_documento,
                'fecha_inicio_lectura' => $consumo->fecha_inicio_lectura,
                'fecha_fin_lectura' => $consumo->fecha_fin_lectura,
                'consumo' => $consumo->consumo,
                'lectura_estimada' => $consumo->lectura_estimada,
                'monto_total' => $consumo->monto_total,
            ])
            ->all());
    }
}
