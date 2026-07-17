<?php

namespace App\Http\Resources\PagoProveedores;

use App\Enums\PagoProveedores\InstanciaRevision;
use App\Models\CasoPagoProveedor;
use App\Models\EgresoCguItem;
use App\Models\Factura;
use App\Models\RegistroContableCgu;
use App\Models\RegistroPagoBancario;
use App\Models\SnapshotDatosExterno;
use App\Services\PagoProveedores\RevisionEgresoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CasoPagoProveedor */
class CasoPagoProveedorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sgf_id' => $this->sgf_id,
            'proveedor' => [
                'nombre' => $this->proveedor?->nombre,
                'rutproveedor' => $this->proveedor?->rutproveedor,
            ],
            'monto' => $this->monto,
            'sgf_status' => $this->sgf_status,
            'sgf_current_group_raw' => $this->sgf_current_group_raw,
            'periodo' => $this->periodo,
            'observacion' => $this->observacion,
            'folio_egreso' => $this->folio_egreso,
            'numero' => $this->numero,
            'fecha_sii' => $this->fecha_sii,
            'observacion_egreso' => $this->observacion_egreso,
            'proceso' => new ProcesoResource($this->proceso),
            'listo_para_aprobar' => $this->listoParaAprobar(),
            'proceso_adquisicion' => $this->whenLoaded(
                'procesoAdquisicion',
                fn () => $this->procesoAdquisicion === null ? null : [
                    'id' => $this->procesoAdquisicion->id,
                    'codigo' => $this->procesoAdquisicion->codigo,
                    'objeto' => $this->procesoAdquisicion->objeto,
                ],
            ),
            'registros_contables_cgu' => $this->whenLoaded(
                'registrosContablesCgu',
                fn () => $this->mapRegistrosContablesCgu(),
            ),
            'registros_pago_bancario' => $this->whenLoaded(
                'registrosPagoBancario',
                fn () => $this->mapRegistrosPagoBancario(),
            ),
            'snapshots_sgf' => $this->whenLoaded(
                'snapshotsSgf',
                fn () => $this->mapSnapshotsSgf(),
            ),
            'egresos_cgu' => $this->whenLoaded(
                'egresoCguItems',
                fn () => $this->mapEgresosCgu(),
            ),
            'facturas' => $this->whenLoaded(
                'facturas',
                fn () => $this->mapFacturas(),
            ),
        ];
    }

    /**
     * Indicador puramente informativo para el listado: refleja si el caso
     * cumple, en su instancia de revisión activa, el mismo criterio que ya
     * habilita la aprobación manual en Revisión de Pagos (documentos
     * obligatorios aprobados y totales verificados). No implica ningún
     * cambio de estado — el `Proceso` solo avanza cuando un revisor con
     * permiso ejecuta la aprobación vía RevisionEgresoService::aprobarPago().
     */
    private function listoParaAprobar(): bool
    {
        $codigoEstado = $this->proceso?->estadoActual?->codigo;

        if (! in_array($codigoEstado, [InstanciaRevision::Finanzas->estado(), InstanciaRevision::Zonal->estado()], true)) {
            return false;
        }

        return app(RevisionEgresoService::class)->pagoListoParaAprobar($this->resource);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapRegistrosContablesCgu(): array
    {
        return array_values($this->registrosContablesCgu
            ->map(fn (RegistroContableCgu $registro) => [
                'id' => $registro->id,
                'numero_registro' => $registro->numero_registro,
                'fecha_registro' => $registro->fecha_registro,
                'monto' => $registro->monto,
                'observaciones' => $registro->observaciones,
                'registrado_por' => $registro->registradoPor?->name,
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapRegistrosPagoBancario(): array
    {
        return array_values($this->registrosPagoBancario
            ->map(fn (RegistroPagoBancario $registro) => [
                'id' => $registro->id,
                'numero_operacion' => $registro->numero_operacion,
                'fecha_pago' => $registro->fecha_pago,
                'monto' => $registro->monto,
                'banco' => $registro->banco,
                'registrado_por' => $registro->registradoPor?->name,
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapSnapshotsSgf(): array
    {
        return array_values($this->snapshotsSgf
            ->map(fn (SnapshotDatosExterno $snapshot) => [
                'id' => $snapshot->id,
                'capturado_en' => $snapshot->capturado_en,
                'hash' => $snapshot->hash,
                'metodo_captura' => $snapshot->metodo_captura,
                'payload_crudo' => $snapshot->payload_crudo,
                'payload_normalizado' => $snapshot->payload_normalizado,
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapEgresosCgu(): array
    {
        return array_values($this->egresoCguItems
            ->map(fn (EgresoCguItem $item) => [
                'id' => $item->egreso->id,
                'numero_egreso' => $item->egreso->numero_egreso,
                'fecha' => $item->egreso->fecha,
                'monto' => $item->monto,
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapFacturas(): array
    {
        return array_values($this->facturas
            ->map(fn (Factura $factura) => [
                'id' => $factura->id,
                'folio' => $factura->folio,
                'monto' => $factura->monto,
                'fecha_emision' => $factura->fecha_emision,
            ])
            ->all());
    }
}
