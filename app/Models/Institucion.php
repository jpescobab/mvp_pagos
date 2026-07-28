<?php

namespace App\Models;

use App\Models\Concerns\RegistraAuditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institucion extends Model
{
    use RegistraAuditoria;

    protected $table = 'instituciones';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Jurisdiccion, $this>
     */
    public function jurisdicciones(): HasMany
    {
        return $this->hasMany(Jurisdiccion::class);
    }
}
