<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    protected $fillable = ['codigo', 'nombre', 'descripcion', 'es_obligatorio', 'activo'];

    protected function casts(): array
    {
        return [
            'es_obligatorio' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * @return HasMany<DocumentRequirement, $this>
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(DocumentRequirement::class);
    }
}
