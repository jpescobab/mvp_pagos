<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConectorAutomatizacionNavegador extends Model
{
    protected $table = 'conectores_automatizacion_navegador';

    protected $fillable = [
        'sistema_externo_id',
        'codigo',
        'nombre',
        'activo',
        'autorizado_por',
        'autorizado_en',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'autorizado_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SistemaExterno, $this>
     */
    public function sistemaExterno(): BelongsTo
    {
        return $this->belongsTo(SistemaExterno::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function autorizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }

    /**
     * @return HasMany<PerfilAutenticacionNavegador, $this>
     */
    public function perfilesAutenticacionNavegador(): HasMany
    {
        return $this->hasMany(PerfilAutenticacionNavegador::class);
    }

    /**
     * @return HasMany<EjecucionAutomatizacionNavegador, $this>
     */
    public function ejecucionesAutomatizacionNavegador(): HasMany
    {
        return $this->hasMany(EjecucionAutomatizacionNavegador::class);
    }

    public function estaAutorizado(): bool
    {
        return $this->activo && $this->autorizado_por !== null && $this->autorizado_en !== null;
    }
}
