<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetricaInformeRazonado extends Model
{
    protected $table = 'metricas_informe_razonado';

    protected $fillable = [
        'ejecucion_informe_razonado_id',
        'seccion_informe_razonado_id',
        'codigo',
        'etiqueta',
        'valor',
        'unidad',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:4',
        ];
    }

    /**
     * @return BelongsTo<EjecucionInformeRazonado, $this>
     */
    public function ejecucionInformeRazonado(): BelongsTo
    {
        return $this->belongsTo(EjecucionInformeRazonado::class);
    }

    /**
     * @return BelongsTo<SeccionInformeRazonado, $this>
     */
    public function seccionInformeRazonado(): BelongsTo
    {
        return $this->belongsTo(SeccionInformeRazonado::class);
    }
}
