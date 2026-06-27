<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed> $payload_crudo
 */
class SnapshotCorteReportabilidad extends Model
{
    public $timestamps = false;

    protected $table = 'snapshots_corte_reportabilidad';

    protected $fillable = [
        'corte_reportabilidad_id',
        'corte_reportabilidad_item_id',
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
     * @return BelongsTo<CorteReportabilidad, $this>
     */
    public function corteReportabilidad(): BelongsTo
    {
        return $this->belongsTo(CorteReportabilidad::class);
    }

    /**
     * @return BelongsTo<CorteReportabilidadItem, $this>
     */
    public function corteReportabilidadItem(): BelongsTo
    {
        return $this->belongsTo(CorteReportabilidadItem::class);
    }
}
