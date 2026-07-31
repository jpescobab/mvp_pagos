<?php

namespace App\Http\Resources\Presupuesto;

use App\Http\Resources\PagoProveedores\ProcesoResource;
use App\Models\Presupuesto\CertificadoDisponibilidadPresupuestaria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CertificadoDisponibilidadPresupuestaria */
class CertificadoDisponibilidadPresupuestariaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'folio' => $this->folio,
            'presupuesto_id' => $this->presupuesto_id,
            'cfinanciero' => [
                'codigo' => $this->cfinanciero?->codigo,
                'nombre' => $this->cfinanciero?->nombre,
            ],
            'denominacion' => $this->denominacion,
            'unidad_ejecutora' => $this->unidad_ejecutora,
            'n_ue' => $this->n_ue,
            'subtitulo' => $this->subtitulo,
            'tipo_gasto' => $this->tipo_gasto,
            'codigo_iniciativa' => $this->codigo_iniciativa,
            'nombre' => $this->nombre,
            'nombre_iniciativa' => $this->nombre_iniciativa,
            'programa_presupuestario' => $this->programa_presupuestario,
            'caracter_gasto' => $this->caracter_gasto,
            'medio_solicitud' => $this->medio_solicitud,
            'fecha_solicitud' => $this->fecha_solicitud,
            'moneda_compra' => $this->moneda_compra,
            'total_moneda_compra' => $this->total_moneda_compra,
            'fecha_paridad' => $this->fecha_paridad,
            'paridad' => $this->paridad,
            'monto' => $this->monto,
            'anio_validez' => $this->anio_validez,
            'requerimiento_numero' => $this->requerimiento_numero,
            'mercado_publico_tipo' => $this->mercado_publico_tipo,
            'mercado_publico_id' => $this->mercado_publico_id,
            'proceso_adquisicion' => $this->whenLoaded('procesoAdquisicion', fn () => $this->procesoAdquisicion === null ? null : [
                'id' => $this->procesoAdquisicion->id,
                'codigo' => $this->procesoAdquisicion->codigo,
            ]),
            'saldo_disponible_al_emitir' => $this->saldo_disponible_al_emitir,
            'hubo_sobregiro_al_emitir' => $this->hubo_sobregiro_al_emitir,
            'cdp_original' => $this->whenLoaded('cdpOriginal', fn () => $this->cdpOriginal === null ? null : [
                'id' => $this->cdpOriginal->id,
                'folio' => $this->cdpOriginal->folio,
            ]),
            'firmado_por' => $this->whenLoaded('firmadoPor', fn () => $this->firmadoPor?->name),
            'firmado_en' => $this->firmado_en,
            'proceso' => $this->whenLoaded('proceso', fn () => new ProcesoResource($this->proceso)),
        ];
    }
}
