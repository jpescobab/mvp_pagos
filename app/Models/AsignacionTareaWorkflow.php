<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsignacionTareaWorkflow extends Model
{
    protected $table = 'asignaciones_tareas_workflow';

    public $timestamps = false;

    protected $fillable = ['tarea_workflow_id', 'user_id', 'asignado_en'];

    protected function casts(): array
    {
        return [
            'asignado_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TareaWorkflow, $this>
     */
    public function tareaWorkflow(): BelongsTo
    {
        return $this->belongsTo(TareaWorkflow::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
