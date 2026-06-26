<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property list<string>|null $documentos_requeridos
 */
class WorkflowTransition extends Model
{
    protected $fillable = [
        'workflow_definition_id',
        'from_state_id',
        'to_state_id',
        'codigo',
        'nombre',
        'permiso_requerido',
        'documentos_requeridos',
        'requiere_comentario',
    ];

    protected function casts(): array
    {
        return [
            'documentos_requeridos' => 'array',
            'requiere_comentario' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<WorkflowDefinition, $this>
     */
    public function workflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }

    /**
     * @return BelongsTo<WorkflowState, $this>
     */
    public function fromState(): BelongsTo
    {
        return $this->belongsTo(WorkflowState::class, 'from_state_id');
    }

    /**
     * @return BelongsTo<WorkflowState, $this>
     */
    public function toState(): BelongsTo
    {
        return $this->belongsTo(WorkflowState::class, 'to_state_id');
    }
}
