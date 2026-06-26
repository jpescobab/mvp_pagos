<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRequirement extends Model
{
    protected $fillable = [
        'document_requirement_set_id',
        'document_type_id',
        'workflow_definition_id',
        'modalidad_id',
        'workflow_state_id',
        'monto_desde',
        'monto_hasta',
        'tipo_requisito',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'monto_desde' => 'decimal:2',
            'monto_hasta' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<DocumentRequirementSet, $this>
     */
    public function requirementSet(): BelongsTo
    {
        return $this->belongsTo(DocumentRequirementSet::class, 'document_requirement_set_id');
    }

    /**
     * @return BelongsTo<DocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * @return BelongsTo<WorkflowDefinition, $this>
     */
    public function workflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }

    /**
     * @return BelongsTo<ProcurementModality, $this>
     */
    public function modalidad(): BelongsTo
    {
        return $this->belongsTo(ProcurementModality::class, 'modalidad_id');
    }

    /**
     * @return BelongsTo<WorkflowState, $this>
     */
    public function workflowState(): BelongsTo
    {
        return $this->belongsTo(WorkflowState::class);
    }
}
