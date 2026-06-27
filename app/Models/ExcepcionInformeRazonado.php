<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ExcepcionInformeRazonado extends Model
{
    protected $table = 'excepciones_informe_razonado';

    protected $fillable = [
        'ejecucion_informe_razonado_id',
        'codigo',
        'descripcion',
        'severidad',
        'vinculable_type',
        'vinculable_id',
    ];

    /**
     * @return BelongsTo<EjecucionInformeRazonado, $this>
     */
    public function ejecucionInformeRazonado(): BelongsTo
    {
        return $this->belongsTo(EjecucionInformeRazonado::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function vinculable(): MorphTo
    {
        return $this->morphTo();
    }
}
