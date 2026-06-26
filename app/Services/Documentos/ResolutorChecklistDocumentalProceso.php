<?php

namespace App\Services\Documentos;

use App\Models\ChecklistDocumentalProceso;
use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\Proceso;
use App\Models\RequisitoDocumental;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ResolutorChecklistDocumentalProceso
{
    /**
     * Resuelve los requisitos_documentales aplicables al proceso (workflow, modalidad,
     * monto y estado actual) dentro de un conjunto y genera/actualiza su checklist.
     */
    public function resolve(Proceso $proceso, ConjuntoRequisitosDocumentales $conjuntoRequisitos, ?User $user = null): ChecklistDocumentalProceso
    {
        $requisitos = $this->requisitosAplicables($proceso, $conjuntoRequisitos);

        return DB::transaction(function () use ($proceso, $conjuntoRequisitos, $requisitos, $user) {
            $checklist = ChecklistDocumentalProceso::updateOrCreate(
                ['proceso_id' => $proceso->id],
                [
                    'conjunto_requisitos_documentales_id' => $conjuntoRequisitos->id,
                    'generado_en' => now(),
                    'generado_por' => $user?->id,
                ],
            );

            $checklist->items()->delete();

            foreach ($requisitos as $requisito) {
                $checklist->items()->create([
                    'requisito_documental_id' => $requisito->id,
                    'tipo_documento_id' => $requisito->tipo_documento_id,
                    'tipo_requisito' => $requisito->tipo_requisito,
                    'estado_cumplimiento' => 'pendiente',
                ]);
            }

            return $checklist->refresh();
        });
    }

    /**
     * @return Collection<int, RequisitoDocumental>
     */
    private function requisitosAplicables(Proceso $proceso, ConjuntoRequisitosDocumentales $conjuntoRequisitos): Collection
    {
        return $conjuntoRequisitos->requisitos()
            ->where('activo', true)
            ->where('definicion_workflow_id', $proceso->definicion_workflow_id)
            ->where(function ($query) use ($proceso) {
                $query->whereNull('modalidad_id')->orWhere('modalidad_id', $proceso->modalidad_id);
            })
            ->where(function ($query) use ($proceso) {
                $query->whereNull('estado_workflow_id')->orWhere('estado_workflow_id', $proceso->estado_actual_id);
            })
            ->where(function ($query) use ($proceso) {
                $query->whereNull('monto_desde')->orWhere('monto_desde', '<=', $proceso->monto ?? 0);
            })
            ->where(function ($query) use ($proceso) {
                $query->whereNull('monto_hasta')->orWhere('monto_hasta', '>=', $proceso->monto ?? 0);
            })
            ->get();
    }
}
