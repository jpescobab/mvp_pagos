<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowState extends Model
{
    protected $fillable = ['workflow_definition_id', 'codigo', 'nombre', 'es_inicial', 'es_final'];

    protected function casts(): array
    {
        return [
            'es_inicial' => 'boolean',
            'es_final' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<WorkflowDefinition, $this>
     */
    public function workflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }
}
