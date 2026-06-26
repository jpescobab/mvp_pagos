<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowTransitionLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'process_id',
        'workflow_transition_id',
        'from_state_id',
        'to_state_id',
        'user_id',
        'comentario',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Process, $this>
     */
    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    /**
     * @return BelongsTo<WorkflowTransition, $this>
     */
    public function transition(): BelongsTo
    {
        return $this->belongsTo(WorkflowTransition::class, 'workflow_transition_id');
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
