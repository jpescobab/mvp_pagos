<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowDefinition extends Model
{
    protected $fillable = ['codigo', 'nombre', 'descripcion', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * @return HasMany<WorkflowState, $this>
     */
    public function states(): HasMany
    {
        return $this->hasMany(WorkflowState::class);
    }

    /**
     * @return HasMany<WorkflowTransition, $this>
     */
    public function transitions(): HasMany
    {
        return $this->hasMany(WorkflowTransition::class);
    }

    /**
     * @return HasMany<Process, $this>
     */
    public function processes(): HasMany
    {
        return $this->hasMany(Process::class);
    }
}
