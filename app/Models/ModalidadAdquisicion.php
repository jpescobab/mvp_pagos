<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModalidadAdquisicion extends Model
{
    protected $table = 'modalidades_adquisicion';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * @return HasMany<RequisitoDocumental, $this>
     */
    public function requisitos(): HasMany
    {
        return $this->hasMany(RequisitoDocumental::class, 'modalidad_id');
    }
}
