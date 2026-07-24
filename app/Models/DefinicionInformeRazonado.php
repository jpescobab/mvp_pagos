<?php

namespace App\Models;

use App\Models\Concerns\RegistraAuditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DefinicionInformeRazonado extends Model
{
    use RegistraAuditoria;

    protected $table = 'definiciones_informe_razonado';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * @return HasMany<EjecucionInformeRazonado, $this>
     */
    public function ejecuciones(): HasMany
    {
        return $this->hasMany(EjecucionInformeRazonado::class);
    }
}
