<?php

namespace App\Http\Resources\ConsumoBasico;

use App\Models\ConsumoBasico;
use App\Models\VinculoDocumento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ConsumoBasico */
class ConsumoBasicoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'caso_pago_proveedor_id' => $this->caso_pago_proveedor_id,
            'numero_documento' => $this->numero_documento,
            'numero_medidor' => $this->numero_medidor,
            'fecha_inicio_lectura' => $this->fecha_inicio_lectura,
            'fecha_fin_lectura' => $this->fecha_fin_lectura,
            'fecha_emision' => $this->fecha_emision,
            'fecha_vencimiento' => $this->fecha_vencimiento,
            'consumo' => $this->consumo,
            'tarifa' => $this->tarifa,
            'lectura_estimada' => $this->lectura_estimada,
            'monto_neto' => $this->monto_neto,
            'iva' => $this->iva,
            'monto_exento' => $this->monto_exento,
            'saldo_anterior' => $this->saldo_anterior,
            'monto_total' => $this->monto_total,
            'cliente_medidor' => $this->whenLoaded(
                'clienteMedidor',
                fn () => [
                    'id' => $this->clienteMedidor->id,
                    'numero_cliente' => $this->clienteMedidor->numero_cliente,
                    'tipo_suministro' => $this->clienteMedidor->tipo_suministro,
                ],
            ),
            'documento' => $this->whenLoaded(
                'vinculosDocumento',
                fn () => $this->vinculosDocumento
                    ->where('activo', true)
                    ->map(fn (VinculoDocumento $vinculo) => [
                        'vinculo_id' => $vinculo->id,
                        'documento_id' => $vinculo->documento->id,
                        'nombre_archivo' => $vinculo->documento->versiones->last()?->nombre_archivo,
                    ])
                    ->first(),
            ),
        ];
    }
}
