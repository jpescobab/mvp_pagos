<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DefinicionWorkflow extends Model
{
    protected $table = 'definiciones_workflow';

    protected $fillable = ['codigo', 'nombre', 'descripcion', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * @return HasMany<EstadoWorkflow, $this>
     */
    public function estados(): HasMany
    {
        return $this->hasMany(EstadoWorkflow::class);
    }

    /**
     * @return HasMany<TransicionWorkflow, $this>
     */
    public function transiciones(): HasMany
    {
        return $this->hasMany(TransicionWorkflow::class);
    }

    /**
     * @return HasMany<Proceso, $this>
     */
    public function procesos(): HasMany
    {
        return $this->hasMany(Proceso::class);
    }
}
