<?php

namespace App\Models;

use App\Models\Concerns\RegistraAuditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoDocumento extends Model
{
    use RegistraAuditoria;

    protected $table = 'tipos_documento';

    protected $fillable = ['codigo', 'nombre', 'descripcion', 'es_obligatorio', 'activo'];

    protected function casts(): array
    {
        return [
            'es_obligatorio' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Documento, $this>
     */
    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class);
    }

    /**
     * @return HasMany<RequisitoDocumental, $this>
     */
    public function requisitos(): HasMany
    {
        return $this->hasMany(RequisitoDocumental::class);
    }
}
