<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NarrativaInformeRazonado extends Model
{
    protected $table = 'narrativas_informe_razonado';

    protected $fillable = [
        'ejecucion_informe_razonado_id',
        'seccion_informe_razonado_id',
        'contenido',
        'generado_por_ia',
        'revisado_por',
        'revisado_en',
    ];

    protected function casts(): array
    {
        return [
            'generado_por_ia' => 'boolean',
            'revisado_en' => 'datetime',
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function revisadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }
}
