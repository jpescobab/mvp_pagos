<?php

namespace App\Services\PagoProveedores;

use App\Models\CasoPagoProveedor;

class ListoParaEgresoResolver
{
    public function __construct(
        private readonly PreparacionEgresoPresenter $preparacionEgreso,
    ) {}

    /**
     * Un caso está listo para Asignar Egreso cuando los 4 criterios del
     * panel de preparación (ver PreparacionEgresoPresenter) están
     * cumplidos: tipo de proceso de pago clasificado, un Traspaso presente,
     * todos los ítems obligatorios del checklist documental cargados (o
     * ninguno, si el tipo de proceso no exige documentos obligatorios), y
     * el proveedor identificado. Asume que el checklist del proceso ya fue
     * resuelto (ver ResolutorChecklistDocumentalProceso).
     */
    public function resuelve(?CasoPagoProveedor $caso): bool
    {
        if ($caso === null) {
            return false;
        }

        return collect($this->preparacionEgreso->criterios($caso))
            ->every(fn (array $criterio) => $criterio['cumplido']);
    }
}
