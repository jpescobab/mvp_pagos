<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeccionInformeRazonado extends Model
{
    protected $table = 'secciones_informe_razonado';

    protected $fillable = [
        'ejecucion_informe_razonado_id',
        'codigo',
        'titulo',
        'orden',
    ];

    /**
     * @return BelongsTo<EjecucionInformeRazonado, $this>
     */
    public function ejecucionInformeRazonado(): BelongsTo
    {
        return $this->belongsTo(EjecucionInformeRazonado::class);
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
     * @return HasMany<NarrativaInformeRazonado, $this>
     */
    public function narrativas(): HasMany
    {
        return $this->hasMany(NarrativaInformeRazonado::class);
    }
}
