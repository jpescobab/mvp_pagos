<?php

namespace App\Services\PagoProveedores;

use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\DefinicionWorkflow;
use App\Models\RequisitoDocumental;
use App\Models\TipoDocumento;
use Illuminate\Support\Collection;

class RequisitoDocumentalPagoProveedorService
{
    public function conjunto(): ConjuntoRequisitosDocumentales
    {
        return ConjuntoRequisitosDocumentales::firstOrCreate(
            ['codigo' => 'pago_proveedores'],
            ['nombre' => 'Requisitos documentales de Pago de Proveedores', 'activo' => true],
        );
    }

    /**
     * @return Collection<int, RequisitoDocumental>
     */
    public function vigentes(): Collection
    {
        $conjunto = $this->conjunto();
        $definicion = DefinicionWorkflow::where('codigo', 'pago_proveedores')->first();

        if ($definicion === null) {
            return collect();
        }

        return RequisitoDocumental::query()
            ->where('conjunto_requisitos_documentales_id', $conjunto->id)
            ->where('definicion_workflow_id', $definicion->id)
            ->where('activo', true)
            ->whereNull('modalidad_id')
            ->whereNull('estado_workflow_id')
            ->get(['id', 'tipo_documento_id', 'tipo_proceso_pago_id', 'tipo_requisito']);
    }

    public function actualizar(TipoDocumento $tipoDocumento, ?int $tipoProcesoPagoId, ?string $tipoRequisito): void
    {
        $conjunto = $this->conjunto();
        $definicion = DefinicionWorkflow::where('codigo', 'pago_proveedores')->firstOrFail();

        $existente = RequisitoDocumental::query()
            ->where('conjunto_requisitos_documentales_id', $conjunto->id)
            ->where('definicion_workflow_id', $definicion->id)
            ->where('tipo_documento_id', $tipoDocumento->id)
            ->where('tipo_proceso_pago_id', $tipoProcesoPagoId)
            ->whereNull('modalidad_id')
            ->whereNull('estado_workflow_id')
            ->whereNull('monto_desde')
            ->whereNull('monto_hasta')
            ->first();

        if ($tipoRequisito === null) {
            $existente?->delete();

            return;
        }

        if ($existente !== null) {
            $existente->update(['tipo_requisito' => $tipoRequisito, 'activo' => true]);

            return;
        }

        RequisitoDocumental::create([
            'conjunto_requisitos_documentales_id' => $conjunto->id,
            'definicion_workflow_id' => $definicion->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'tipo_proceso_pago_id' => $tipoProcesoPagoId,
            'modalidad_id' => null,
            'estado_workflow_id' => null,
            'monto_desde' => null,
            'monto_hasta' => null,
            'tipo_requisito' => $tipoRequisito,
            'activo' => true,
        ]);
    }
}
