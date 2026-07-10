<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class EgresoCgu extends Model
{
    protected $table = 'egresos_cgu';

    protected $fillable = [
        'numero_egreso',
        'fecha',
        'monto_total',
        'periodo',
        'cfinanciero_id',
        'generado_automaticamente',
        'observaciones',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto_total' => 'decimal:2',
            'generado_automaticamente' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    /**
     * @return BelongsTo<Cfinanciero, $this>
     */
    public function cfinanciero(): BelongsTo
    {
        return $this->belongsTo(Cfinanciero::class);
    }

    /**
     * Jurisdicción a la que pertenece el egreso, derivada de su centro
     * financiero. Gobierna el alcance zonal del Administrador Zonal.
     */
    public function jurisdiccionId(): ?int
    {
        return $this->cfinanciero?->jurisdiccion_id;
    }

    /**
     * @return HasMany<EgresoCguItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(EgresoCguItem::class);
    }

    /**
     * @return MorphMany<VinculoDocumento, $this>
     */
    public function vinculosDocumento(): MorphMany
    {
        return $this->morphMany(VinculoDocumento::class, 'vinculable');
    }
}
