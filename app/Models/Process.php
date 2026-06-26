<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property list<string>|null $documentos_adjuntos
 */
class Process extends Model
{
    protected $fillable = [
        'workflow_definition_id',
        'current_state_id',
        'subject_type',
        'subject_id',
        'documentos_adjuntos',
        'iniciado_por',
        'cerrado_en',
    ];

    protected function casts(): array
    {
        return [
            'documentos_adjuntos' => 'array',
            'cerrado_en' => 'datetime',
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
    public function currentState(): BelongsTo
    {
        return $this->belongsTo(WorkflowState::class, 'current_state_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function iniciadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'iniciado_por');
    }

    /**
     * @return HasMany<WorkflowTask, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(WorkflowTask::class);
    }

    /**
     * @return HasMany<WorkflowTransitionLog, $this>
     */
    public function transitionLogs(): HasMany
    {
        return $this->hasMany(WorkflowTransitionLog::class);
    }
}
