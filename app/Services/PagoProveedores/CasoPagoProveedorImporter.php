<?php

namespace App\Services\PagoProveedores;

use App\Models\CasoPagoProveedor;
use App\Models\DefinicionWorkflow;
use App\Models\EgresoCgu;
use App\Models\EgresoCguItem;
use App\Models\Proceso;
use App\Models\Proveedor;
use App\Models\SnapshotDatosExterno;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class CasoPagoProveedorImporter
{
    /**
     * Formatos aceptados para `fecha_sii` tal como puede llegar desde SGF.
     *
     * @var list<string>
     */
    private const FORMATOS_FECHA_SII = ['d-m-Y', 'd/m/Y', 'Y-m-d'];

    public function importarDesdeSnapshot(SnapshotDatosExterno $snapshot): CasoPagoProveedor
    {
        $normalizado = $snapshot->payload_normalizado;

        $caso = CasoPagoProveedor::where('sgf_id', $snapshot->referencia_externa)->first();

        if ($caso !== null) {
            $caso->update([
                'rut_proveedor' => $normalizado['rut'],
                'monto' => $normalizado['monto'],
                'sgf_status' => $normalizado['estado'],
                'sgf_current_group_raw' => $normalizado['grupo_actual'],
                'periodo' => $normalizado['periodo'] ?? null,
                'observacion' => $normalizado['observacion'] ?? null,
                'folio_egreso' => $normalizado['folio_egreso'] ?? null,
                'numero' => $normalizado['numero'] ?? null,
                'fecha_sii' => $this->parseFechaSii($normalizado['fecha_sii'] ?? null),
                'observacion_egreso' => $normalizado['observacion_egreso'] ?? null,
            ]);

            $caso->proceso?->update(['monto' => $normalizado['monto']]);

            $this->agruparEnEgreso($caso);

            return $caso->refresh();
        }

        return DB::transaction(function () use ($snapshot, $normalizado) {
            $caso = CasoPagoProveedor::create([
                'sgf_id' => $snapshot->referencia_externa,
                'proveedor_id' => Proveedor::where('rutproveedor', Proveedor::normalizarRut($normalizado['rut']))->value('id'),
                'rut_proveedor' => $normalizado['rut'],
                'monto' => $normalizado['monto'],
                'sgf_status' => $normalizado['estado'],
                'sgf_current_group_raw' => $normalizado['grupo_actual'],
                'periodo' => $normalizado['periodo'] ?? null,
                'observacion' => $normalizado['observacion'] ?? null,
                'folio_egreso' => $normalizado['folio_egreso'] ?? null,
                'numero' => $normalizado['numero'] ?? null,
                'fecha_sii' => $this->parseFechaSii($normalizado['fecha_sii'] ?? null),
                'observacion_egreso' => $normalizado['observacion_egreso'] ?? null,
            ]);

            $definicion = DefinicionWorkflow::where('codigo', 'pago_proveedores')->firstOrFail();
            $estadoInicial = $definicion->estados()->where('es_inicial', true)->firstOrFail();

            Proceso::create([
                'definicion_workflow_id' => $definicion->id,
                'estado_actual_id' => $estadoInicial->id,
                'sujeto_type' => CasoPagoProveedor::class,
                'sujeto_id' => $caso->id,
                'monto' => $normalizado['monto'],
            ]);

            $this->agruparEnEgreso($caso);

            return $caso->refresh();
        });
    }

    /**
     * Agrupa automáticamente el caso en un Egreso CGU según el folio de egreso
     * que entrega SGF. SGF ya agrupa los pagos en egresos; usamos ese folio
     * como clave natural. La agrupación no altera el `sgf_id`, snapshots ni el
     * historial del caso, y puede ajustarse manualmente antes de revisar.
     */
    private function agruparEnEgreso(CasoPagoProveedor $caso): void
    {
        $folio = $caso->folio_egreso;

        if ($folio === null || trim($folio) === '') {
            return;
        }

        $egreso = EgresoCgu::firstOrCreate(
            ['numero_egreso' => $folio],
            [
                'fecha' => $caso->fecha_sii ?? now()->toDateString(),
                'periodo' => $caso->periodo,
                'cfinanciero_id' => $caso->cfinancieroId(),
                'generado_automaticamente' => true,
            ],
        );

        EgresoCguItem::firstOrCreate(
            ['egreso_cgu_id' => $egreso->id, 'caso_pago_proveedor_id' => $caso->id],
            ['monto' => $caso->monto],
        );

        $egreso->update([
            'monto_total' => $egreso->items()->sum('monto'),
            'cfinanciero_id' => $egreso->cfinanciero_id ?? $caso->cfinancieroId(),
        ]);
    }

    private function parseFechaSii(?string $valor): ?string
    {
        if ($valor === null || trim($valor) === '') {
            return null;
        }

        foreach (self::FORMATOS_FECHA_SII as $formato) {
            try {
                return Carbon::createFromFormat($formato, trim($valor))->toDateString();
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }
}
