<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndicadorEconomicoImportacion extends Model
{
    protected $table = 'indicadores_economicos_importaciones';

    protected $fillable = [
        'tipo_importacion',
        'estado',
        'indicadores_solicitados',
        'fuente_principal',
        'fuente_fallback',
        'fecha_programada',
        'periodo',
        'fecha_desde',
        'fecha_hasta',
        'iniciado_en',
        'finalizado_en',
        'creado_por_user_id',
        'ejecutado_por_job',
        'total_recibidos',
        'total_creados',
        'total_omitidos',
        'total_fallidos',
        'errores',
        'advertencias',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'indicadores_solicitados' => 'array',
            'fecha_programada' => 'date',
            'fecha_desde' => 'date',
            'fecha_hasta' => 'date',
            'iniciado_en' => 'datetime',
            'finalizado_en' => 'datetime',
            'total_recibidos' => 'integer',
            'total_creados' => 'integer',
            'total_omitidos' => 'integer',
            'total_fallidos' => 'integer',
            'errores' => 'array',
            'advertencias' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * @return HasMany<IndicadorEconomico, $this>
     */
    public function indicadores(): HasMany
    {
        return $this->hasMany(IndicadorEconomico::class, 'importacion_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por_user_id');
    }

    public function marcarComoRunning(): void
    {
        $this->update(['estado' => 'running', 'iniciado_en' => now()]);
    }

    /**
     * @param  array{total_recibidos?: int, total_creados?: int, total_omitidos?: int, total_fallidos?: int, errores?: ?list<string>, advertencias?: ?list<string>}  $conteos
     */
    public function marcarComoFinalizada(string $estado, array $conteos = []): void
    {
        $this->update([
            'estado' => $estado,
            'finalizado_en' => now(),
            ...$conteos,
        ]);
    }
}
