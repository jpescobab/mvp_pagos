<?php

namespace App\Services\PagoProveedores;

use App\Models\CasoPagoProveedor;
use App\Models\DefinicionWorkflow;
use App\Models\Proceso;
use App\Models\Proveedor;
use App\Models\SnapshotDatosExterno;
use Illuminate\Support\Facades\DB;

class CasoPagoProveedorImporter
{
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
            ]);

            $caso->proceso?->update(['monto' => $normalizado['monto']]);

            return $caso->refresh();
        }

        return DB::transaction(function () use ($snapshot, $normalizado) {
            $caso = CasoPagoProveedor::create([
                'sgf_id' => $snapshot->referencia_externa,
                'proveedor_id' => Proveedor::where('rutproveedor', $normalizado['rut'])->value('id'),
                'rut_proveedor' => $normalizado['rut'],
                'monto' => $normalizado['monto'],
                'sgf_status' => $normalizado['estado'],
                'sgf_current_group_raw' => $normalizado['grupo_actual'],
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

            return $caso->refresh();
        });
    }
}
