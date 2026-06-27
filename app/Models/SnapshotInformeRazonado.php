<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed> $payload_crudo
 */
class SnapshotInformeRazonado extends Model
{
    public $timestamps = false;

    protected $table = 'snapshots_informe_razonado';

    protected $fillable = [
        'ejecucion_informe_razonado_id',
        'payload_crudo',
        'hash',
        'capturado_en',
    ];

    protected function casts(): array
    {
        return [
            'payload_crudo' => 'array',
            'capturado_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<EjecucionInformeRazonado, $this>
     */
    public function ejecucionInformeRazonado(): BelongsTo
    {
        return $this->belongsTo(EjecucionInformeRazonado::class);
    }
}
