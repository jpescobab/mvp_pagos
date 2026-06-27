<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AprobacionInformeRazonado extends Model
{
    public $timestamps = false;

    protected $table = 'aprobaciones_informe_razonado';

    protected $fillable = [
        'ejecucion_informe_razonado_id',
        'aprobado_por',
        'decision',
        'comentario',
        'decidido_en',
    ];

    protected function casts(): array
    {
        return [
            'decidido_en' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }
}
