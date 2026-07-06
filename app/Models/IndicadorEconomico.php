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
        'codigo',
        'nombre',
        'tipo',
        'fecha_valor',
        'periodo',
        'valor',
        'periodicidad_valor',
        'periodicidad_publicacion',
        'vigente_desde',
        'vigente_hasta',
        'unidad_medida',
        'moneda_base',
        'fuente',
        'endpoint',
        'source_url',
        'source_hash',
        'source_payload',
        'capturado_en',
        'capturado_por_user_id',
        'capturado_por_job',
        'requiere_dia_habil',
        'es_proyectado',
        'es_oficial',
        'activo',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'fecha_valor' => 'date:Y-m-d',
            'vigente_desde' => 'date:Y-m-d',
            'vigente_hasta' => 'date:Y-m-d',
            'valor' => 'decimal:4',
            'source_payload' => 'array',
            'capturado_en' => 'datetime',
            'requiere_dia_habil' => 'boolean',
            'es_proyectado' => 'boolean',
            'es_oficial' => 'boolean',
            'activo' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<IndicadorEconomicoImportacion, $this>
     */
    public function importacion(): BelongsTo
    {
        return $this->belongsTo(IndicadorEconomicoImportacion::class, 'importacion_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function capturadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'capturado_por_user_id');
    }
}
