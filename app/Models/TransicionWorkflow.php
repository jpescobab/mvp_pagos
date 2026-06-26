<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property list<string>|null $documentos_requeridos
 */
class TransicionWorkflow extends Model
{
    protected $table = 'transiciones_workflow';

    protected $fillable = [
        'definicion_workflow_id',
        'estado_origen_id',
        'estado_destino_id',
        'codigo',
        'nombre',
        'permiso_requerido',
        'documentos_requeridos',
        'requiere_comentario',
    ];

    protected function casts(): array
    {
        return [
            'documentos_requeridos' => 'array',
            'requiere_comentario' => 'boolean',
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
}
