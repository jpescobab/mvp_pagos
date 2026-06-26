<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowTask extends Model
{
    protected $fillable = ['process_id', 'workflow_transition_id', 'titulo', 'descripcion', 'estado', 'vence_en'];

    protected function casts(): array
    {
        return [
            'vence_en' => 'date',
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
     * @return HasMany<WorkflowTaskAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(WorkflowTaskAssignment::class);
    }
}
