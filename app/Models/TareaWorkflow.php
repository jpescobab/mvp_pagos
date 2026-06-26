<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TareaWorkflow extends Model
{
    protected $table = 'tareas_workflow';

    protected $fillable = ['proceso_id', 'transicion_workflow_id', 'titulo', 'descripcion', 'estado', 'vence_en'];

    protected function casts(): array
    {
        return [
            'vence_en' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Proceso, $this>
     */
    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class);
    }

    /**
     * @return BelongsTo<TransicionWorkflow, $this>
     */
    public function transicion(): BelongsTo
    {
        return $this->belongsTo(TransicionWorkflow::class, 'transicion_workflow_id');
    }

    /**
     * @return HasMany<AsignacionTareaWorkflow, $this>
     */
    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionTareaWorkflow::class);
    }
}
