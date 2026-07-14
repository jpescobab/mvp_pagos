<?php

namespace App\Services\PagoProveedores;

use App\Models\CasoPagoProveedor;

class ListoParaEgresoResolver
{
    /**
     * Un caso está listo para Asignar Egreso cuando: tiene tipo de proceso
     * de pago clasificado, al menos un Traspaso (RegistroContableCgu)
     * registrado, todos los ítems obligatorios del checklist documental
     * cargados, y el proveedor identificado. Asume que el checklist del
     * proceso ya fue resuelto (ver ResolutorChecklistDocumentalProceso).
     */
    public function resuelve(?CasoPagoProveedor $caso): bool
    {
        if ($caso === null || $caso->proceso === null || $caso->proveedor_id === null) {
            return false;
        }

        if ($caso->proceso->tipo_proceso_pago_id === null) {
            return false;
        }

        if ($caso->registrosContablesCgu->isEmpty()) {
            return false;
        }

        $checklist = $caso->proceso->checklist;

        if ($checklist === null) {
            return false;
        }

        return $checklist->items
            ->where('tipo_requisito', 'obligatorio')
            ->every(fn ($item) => $item->documento_id !== null);
    }
}
