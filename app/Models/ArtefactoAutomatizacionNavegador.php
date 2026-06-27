<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArtefactoAutomatizacionNavegador extends Model
{
    public $timestamps = false;

    protected $table = 'artefactos_automatizacion_navegador';

    protected $fillable = [
        'ejecucion_automatizacion_navegador_id',
        'paso_automatizacion_navegador_id',
        'tipo',
        'ruta_almacenamiento',
        'hash',
        'capturado_en',
    ];

    protected function casts(): array
    {
        return [
            'capturado_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<EjecucionAutomatizacionNavegador, $this>
     */
    public function ejecucionAutomatizacionNavegador(): BelongsTo
    {
        return $this->belongsTo(EjecucionAutomatizacionNavegador::class);
    }

    /**
     * @return BelongsTo<PasoAutomatizacionNavegador, $this>
     */
    public function pasoAutomatizacionNavegador(): BelongsTo
    {
        return $this->belongsTo(PasoAutomatizacionNavegador::class);
    }
}
