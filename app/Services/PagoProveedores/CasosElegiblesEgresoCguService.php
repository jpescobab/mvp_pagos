<?php

namespace App\Services\PagoProveedores;

use App\Models\CasoPagoProveedor;
use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\TrabajoIntegracion;
use App\Services\Documentos\ResolutorChecklistDocumentalProceso;

class CasosElegiblesEgresoCguService
{
    public function __construct(
        private readonly ResolutorChecklistDocumentalProceso $resolutorChecklist,
        private readonly ListoParaEgresoResolver $listoParaEgreso,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function paraFormulario(?int $trabajoIntegracionId): array
    {
        $query = CasoPagoProveedor::whereDoesntHave('egresoCguItems')->with([
            'proveedor',
            'proceso.checklist.items',
            'proceso.tipoProcesoPago',
            'registrosContablesCgu',
        ]);

        if ($trabajoIntegracionId !== null) {
            $trabajoIntegracion = TrabajoIntegracion::with('snapshotsDatosExternos')->find($trabajoIntegracionId);
            $sgfIds = $trabajoIntegracion?->snapshotsDatosExternos->pluck('referencia_externa')->unique() ?? collect();

            $query->whereIn('sgf_id', $sgfIds);
        }

        $conjuntoRequisitos = ConjuntoRequisitosDocumentales::where('codigo', 'pago_proveedores')->first();

        return $query->get()->map(function (CasoPagoProveedor $caso) use ($conjuntoRequisitos) {
            if ($conjuntoRequisitos !== null && $caso->proceso !== null) {
                $this->resolutorChecklist->resolve($caso->proceso, $conjuntoRequisitos);
                $caso->proceso->load('checklist.items');
            }

            return [
                'id' => $caso->id,
                'sgf_id' => $caso->sgf_id,
                'proveedor' => ['nombre' => $caso->proveedor?->nombre],
                'monto' => $caso->monto,
                'listo' => $this->listoParaEgreso->resuelve($caso),
            ];
        })->all();
    }
}
