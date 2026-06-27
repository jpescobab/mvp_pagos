<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodoReportabilidad extends Model
{
    protected $table = 'periodos_reportabilidad';

    protected $fillable = [
        'codigo',
        'fecha_inicio',
        'fecha_fin',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    /**
     * @return HasMany<CorteReportabilidad, $this>
     */
    public function cortesReportabilidad(): HasMany
    {
        return $this->hasMany(CorteReportabilidad::class);
    }
}
