<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed>|null $detalle
 */
class PasoAutomatizacionNavegador extends Model
{
    public $timestamps = false;

    protected $table = 'pasos_automatizacion_navegador';

    protected $fillable = [
        'ejecucion_automatizacion_navegador_id',
        'orden',
        'accion',
        'detalle',
        'estado',
        'error',
        'ejecutado_en',
    ];

    protected function casts(): array
    {
        return [
            'detalle' => 'array',
            'ejecutado_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<EjecucionAutomatizacionNavegador, $this>
     */
    public function ejecucionAutomatizacionNavegador(): BelongsTo
    {
        return $this->belongsTo(EjecucionAutomatizacionNavegador::class);
    }
}
