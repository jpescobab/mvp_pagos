<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowTaskAssignment extends Model
{
    public $timestamps = false;

    protected $fillable = ['workflow_task_id', 'user_id', 'asignado_en'];

    protected function casts(): array
    {
        return [
            'asignado_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<WorkflowTask, $this>
     */
    public function workflowTask(): BelongsTo
    {
        return $this->belongsTo(WorkflowTask::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
