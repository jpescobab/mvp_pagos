<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Proceso extends Model
{
    protected $fillable = [
        'definicion_workflow_id',
        'estado_actual_id',
        'sujeto_type',
        'sujeto_id',
        'modalidad_id',
        'monto',
        'iniciado_por',
        'cerrado_en',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'cerrado_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DefinicionWorkflow, $this>
     */
    public function definicionWorkflow(): BelongsTo
    {
        return $this->belongsTo(DefinicionWorkflow::class);
    }

    /**
     * @return BelongsTo<EstadoWorkflow, $this>
     */
    public function estadoActual(): BelongsTo
    {
        return $this->belongsTo(EstadoWorkflow::class, 'estado_actual_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function sujeto(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<ModalidadAdquisicion, $this>
     */
    public function modalidad(): BelongsTo
    {
        return $this->belongsTo(ModalidadAdquisicion::class, 'modalidad_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function iniciadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'iniciado_por');
    }

    /**
     * @return HasMany<TareaWorkflow, $this>
     */
    public function tareas(): HasMany
    {
        return $this->hasMany(TareaWorkflow::class);
    }

    /**
     * @return HasMany<HistorialTransicionWorkflow, $this>
     */
    public function historialTransiciones(): HasMany
    {
        return $this->hasMany(HistorialTransicionWorkflow::class);
    }

    /**
     * @return MorphMany<VinculoDocumento, $this>
     */
    public function vinculosDocumento(): MorphMany
    {
        return $this->morphMany(VinculoDocumento::class, 'vinculable');
    }

    /**
     * @return HasOne<ChecklistDocumentalProceso, $this>
     */
    public function checklist(): HasOne
    {
        return $this->hasOne(ChecklistDocumentalProceso::class);
    }
}
