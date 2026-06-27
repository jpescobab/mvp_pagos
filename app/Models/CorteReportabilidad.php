<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CorteReportabilidad extends Model
{
    protected $table = 'cortes_reportabilidad';

    protected $fillable = [
        'periodo_reportabilidad_id',
        'fecha_corte',
        'estado',
        'publicado_por',
        'publicado_en',
    ];

    protected function casts(): array
    {
        return [
            'fecha_corte' => 'datetime',
            'publicado_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PeriodoReportabilidad, $this>
     */
    public function periodoReportabilidad(): BelongsTo
    {
        return $this->belongsTo(PeriodoReportabilidad::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function publicadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publicado_por');
    }

    /**
     * @return HasMany<CorteReportabilidadItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CorteReportabilidadItem::class);
    }

    /**
     * @return HasMany<SnapshotCorteReportabilidad, $this>
     */
    public function snapshots(): HasMany
    {
        return $this->hasMany(SnapshotCorteReportabilidad::class);
    }

    /**
     * @return HasMany<EjecucionInformeRazonado, $this>
     */
    public function ejecucionesInformeRazonado(): HasMany
    {
        return $this->hasMany(EjecucionInformeRazonado::class);
    }

    public function estaPublicado(): bool
    {
        return $this->estado === 'publicado';
    }
}
