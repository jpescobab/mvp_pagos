<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentRequirementSet extends Model
{
    protected $fillable = ['codigo', 'nombre', 'descripcion', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * @return HasMany<DocumentRequirement, $this>
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(DocumentRequirement::class);
    }
}
