<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class EjecucionInformeRazonado extends Model
{
    protected $table = 'ejecuciones_informe_razonado';

    protected $fillable = [
        'definicion_informe_razonado_id',
        'corte_reportabilidad_id',
        'generado_por',
        'generado_en',
    ];

    protected function casts(): array
    {
        return [
            'generado_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DefinicionInformeRazonado, $this>
     */
    public function definicionInformeRazonado(): BelongsTo
    {
        return $this->belongsTo(DefinicionInformeRazonado::class);
    }

    /**
     * @return BelongsTo<CorteReportabilidad, $this>
     */
    public function corteReportabilidad(): BelongsTo
    {
        return $this->belongsTo(CorteReportabilidad::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function generadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por');
    }

    /**
     * @return MorphOne<Proceso, $this>
     */
    public function proceso(): MorphOne
    {
        return $this->morphOne(Proceso::class, 'sujeto');
    }

    /**
     * @return HasMany<SeccionInformeRazonado, $this>
     */
    public function secciones(): HasMany
    {
        return $this->hasMany(SeccionInformeRazonado::class);
    }

    /**
     * @return HasMany<MetricaInformeRazonado, $this>
     */
    public function metricas(): HasMany
    {
        return $this->hasMany(MetricaInformeRazonado::class);
    }

    /**
     * @return HasMany<GraficoInformeRazonado, $this>
     */
    public function graficos(): HasMany
    {
        return $this->hasMany(GraficoInformeRazonado::class);
    }

    /**
     * @return HasMany<ExcepcionInformeRazonado, $this>
     */
    public function excepciones(): HasMany
    {
        return $this->hasMany(ExcepcionInformeRazonado::class);
    }

    /**
     * @return HasMany<NarrativaInformeRazonado, $this>
     */
    public function narrativas(): HasMany
    {
        return $this->hasMany(NarrativaInformeRazonado::class);
    }

    /**
     * @return HasMany<SnapshotInformeRazonado, $this>
     */
    public function snapshots(): HasMany
    {
        return $this->hasMany(SnapshotInformeRazonado::class);
    }

    /**
     * @return HasMany<AprobacionInformeRazonado, $this>
     */
    public function aprobaciones(): HasMany
    {
        return $this->hasMany(AprobacionInformeRazonado::class);
    }

    /**
     * @return HasMany<ExportacionInformeRazonado, $this>
     */
    public function exportaciones(): HasMany
    {
        return $this->hasMany(ExportacionInformeRazonado::class);
    }
}
