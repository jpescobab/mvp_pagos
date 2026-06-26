<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConjuntoRequisitosDocumentales extends Model
{
    protected $table = 'conjuntos_requisitos_documentales';

    protected $fillable = ['codigo', 'nombre', 'descripcion', 'activo'];

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
        return $this->hasMany(RequisitoDocumental::class);
    }
}
