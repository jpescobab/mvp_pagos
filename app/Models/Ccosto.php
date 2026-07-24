<?php

namespace App\Models;

use App\Models\Concerns\RegistraAuditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ccosto extends Model
{
    use RegistraAuditoria;

    protected $table = 'ccostos';

    protected $fillable = ['cfinanciero_id', 'codigo', 'nombre', 'cod_edificio', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Cfinanciero, $this>
     */
    public function cfinanciero(): BelongsTo
    {
        return $this->belongsTo(Cfinanciero::class);
    }

    /**
     * @return HasMany<ClienteMedidor, $this>
     */
    public function clienteMedidores(): HasMany
    {
        return $this->hasMany(ClienteMedidor::class);
    }

    /**
     * @return HasMany<ProcesoAdquisicion, $this>
     */
    public function procesosAdquisicion(): HasMany
    {
        return $this->hasMany(ProcesoAdquisicion::class);
    }

    /**
     * @return HasMany<Funcionario, $this>
     */
    public function funcionarios(): HasMany
    {
        return $this->hasMany(Funcionario::class);
    }
}
