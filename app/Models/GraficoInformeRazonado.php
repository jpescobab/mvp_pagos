<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed> $datos
 */
class GraficoInformeRazonado extends Model
{
    protected $table = 'graficos_informe_razonado';

    protected $fillable = [
        'ejecucion_informe_razonado_id',
        'seccion_informe_razonado_id',
        'codigo',
        'titulo',
        'tipo',
        'datos',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'datos' => 'array',
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
