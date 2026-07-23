<?php

namespace App\Models;

use App\Models\Concerns\RegistraAuditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoProcesoPago extends Model
{
    use RegistraAuditoria;

    protected $table = 'tipos_proceso_pago';

    protected $fillable = ['codigo', 'nombre', 'activo', 'requiere_traspaso_cgu'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'requiere_traspaso_cgu' => 'boolean',
        ];
    }

    /**
     * @return HasMany<RequisitoDocumental, $this>
     */
    public function requisitos(): HasMany
    {
        return $this->hasMany(RequisitoDocumental::class, 'tipo_proceso_pago_id');
    }

    /**
     * @return HasMany<Proceso, $this>
     */
    public function procesos(): HasMany
    {
        return $this->hasMany(Proceso::class, 'tipo_proceso_pago_id');
    }
}
