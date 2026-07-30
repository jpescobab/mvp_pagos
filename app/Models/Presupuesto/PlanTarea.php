<?php

namespace App\Models\Presupuesto;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanTarea extends Model
{
    use SoftDeletes;

    protected $table = 'planes_tarea';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Presupuesto, $this>
     */
    public function presupuestos(): HasMany
    {
        return $this->hasMany(Presupuesto::class);
    }
}
