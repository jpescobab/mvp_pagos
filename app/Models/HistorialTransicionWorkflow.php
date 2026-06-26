<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialTransicionWorkflow extends Model
{
    protected $table = 'historial_transiciones_workflow';

    public const UPDATED_AT = null;

    protected $fillable = [
        'proceso_id',
        'transicion_workflow_id',
        'estado_origen_id',
        'estado_destino_id',
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
     * @return BelongsTo<EstadoWorkflow, $this>
     */
    public function estadoOrigen(): BelongsTo
    {
        return $this->belongsTo(EstadoWorkflow::class, 'estado_origen_id');
    }

    /**
     * @return BelongsTo<EstadoWorkflow, $this>
     */
    public function estadoDestino(): BelongsTo
    {
        return $this->belongsTo(EstadoWorkflow::class, 'estado_destino_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
