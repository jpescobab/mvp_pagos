<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndicadorEconomicoImportacion extends Model
{
    protected $table = 'indicadores_economicos_importaciones';

    protected $fillable = ['tipo', 'estado', 'endpoint', 'source_payload', 'errores', 'advertencias', 'metadata'];

    protected function casts(): array
    {
        return [
            'source_payload' => 'array',
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
}
