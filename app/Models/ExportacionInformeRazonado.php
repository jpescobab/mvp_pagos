<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportacionInformeRazonado extends Model
{
    public $timestamps = false;

    protected $table = 'exportaciones_informe_razonado';

    protected $fillable = [
        'ejecucion_informe_razonado_id',
        'formato',
        'ruta_archivo',
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
     * @return BelongsTo<EjecucionInformeRazonado, $this>
     */
    public function ejecucionInformeRazonado(): BelongsTo
    {
        return $this->belongsTo(EjecucionInformeRazonado::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function generadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por');
    }
}
