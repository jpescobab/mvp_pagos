<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jurisdiccion extends Model
{
    protected $table = 'jurisdicciones';

    protected $fillable = ['institucion_id', 'codigo', 'nombre', 'descripcion', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Institucion, $this>
     */
    public function institucion(): BelongsTo
    {
        return $this->belongsTo(Institucion::class);
    }

    /**
     * @return HasMany<Cfinanciero, $this>
     */
    public function cfinancieros(): HasMany
    {
        return $this->hasMany(Cfinanciero::class);
    }
}
