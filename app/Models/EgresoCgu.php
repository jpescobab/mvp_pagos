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
        'observaciones',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto_total' => 'decimal:2',
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
