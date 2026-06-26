<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementModality extends Model
{
    protected $fillable = ['codigo', 'nombre', 'activo'];

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
        return $this->hasMany(DocumentRequirement::class, 'modalidad_id');
    }
}
