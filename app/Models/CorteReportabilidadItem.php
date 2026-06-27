<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CorteReportabilidadItem extends Model
{
    public $timestamps = false;

    protected $table = 'cortes_reportabilidad_items';

    protected $fillable = [
        'corte_reportabilidad_id',
        'vinculable_type',
        'vinculable_id',
        'etiqueta',
        'incluido_en',
    ];

    protected function casts(): array
    {
        return [
            'incluido_en' => 'datetime',
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
     * @return MorphTo<Model, $this>
     */
    public function vinculable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<SnapshotCorteReportabilidad, $this>
     */
    public function snapshots(): HasMany
    {
        return $this->hasMany(SnapshotCorteReportabilidad::class);
    }
}
