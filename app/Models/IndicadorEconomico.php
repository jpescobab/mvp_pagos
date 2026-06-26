<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicadorEconomico extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'indicadores_economicos';

    protected $fillable = [
        'importacion_id',
        'tipo',
        'fecha_valor',
        'periodo',
        'valor',
        'periodicidad_valor',
        'periodicidad_publicacion',
        'vigente_desde',
        'vigente_hasta',
        'fuente',
        'source_url',
        'source_hash',
        'source_payload',
        'advertencias',
    ];

    protected function casts(): array
    {
        return [
            'fecha_valor' => 'date',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
            'valor' => 'decimal:4',
            'source_payload' => 'array',
            'advertencias' => 'array',
        ];
    }

    /**
     * @return BelongsTo<IndicadorEconomicoImportacion, $this>
     */
    public function importacion(): BelongsTo
    {
        return $this->belongsTo(IndicadorEconomicoImportacion::class, 'importacion_id');
    }
}
