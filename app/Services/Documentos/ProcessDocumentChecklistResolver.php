<?php

namespace App\Services\Documentos;

use App\Models\DocumentRequirement;
use App\Models\DocumentRequirementSet;
use App\Models\Process;
use App\Models\ProcessDocumentChecklist;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProcessDocumentChecklistResolver
{
    /**
     * Resuelve los document_requirements aplicables al proceso (workflow, modalidad,
     * monto y estado actual) dentro de un set y genera/actualiza su checklist.
     */
    public function resolve(Process $process, DocumentRequirementSet $requirementSet, ?User $user = null): ProcessDocumentChecklist
    {
        $requisitos = $this->requisitosAplicables($process, $requirementSet);

        return DB::transaction(function () use ($process, $requirementSet, $requisitos, $user) {
            $checklist = ProcessDocumentChecklist::updateOrCreate(
                ['process_id' => $process->id],
                [
                    'document_requirement_set_id' => $requirementSet->id,
                    'generated_at' => now(),
                    'generated_by' => $user?->id,
                ],
            );

            $checklist->items()->delete();

            foreach ($requisitos as $requisito) {
                $checklist->items()->create([
                    'document_requirement_id' => $requisito->id,
                    'document_type_id' => $requisito->document_type_id,
                    'tipo_requisito' => $requisito->tipo_requisito,
                    'estado_cumplimiento' => 'pendiente',
                ]);
            }

            return $checklist->refresh();
        });
    }

    /**
     * @return Collection<int, DocumentRequirement>
     */
    private function requisitosAplicables(Process $process, DocumentRequirementSet $requirementSet): Collection
    {
        return $requirementSet->requirements()
            ->where('activo', true)
            ->where('workflow_definition_id', $process->workflow_definition_id)
            ->where(function ($query) use ($process) {
                $query->whereNull('modalidad_id')->orWhere('modalidad_id', $process->modalidad_id);
            })
            ->where(function ($query) use ($process) {
                $query->whereNull('workflow_state_id')->orWhere('workflow_state_id', $process->current_state_id);
            })
            ->where(function ($query) use ($process) {
                $query->whereNull('monto_desde')->orWhere('monto_desde', '<=', $process->monto ?? 0);
            })
            ->where(function ($query) use ($process) {
                $query->whereNull('monto_hasta')->orWhere('monto_hasta', '>=', $process->monto ?? 0);
            })
            ->get();
    }
}
