<?php

namespace App\Models;

use App\Models\Concerns\RegistraAuditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cfinanciero extends Model
{
    use RegistraAuditoria;

    protected $table = 'cfinancieros';

    protected $fillable = ['jurisdiccion_id', 'codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Jurisdiccion, $this>
     */
    public function jurisdiccion(): BelongsTo
    {
        return $this->belongsTo(Jurisdiccion::class);
    }

    /**
     * @return HasMany<Ccosto, $this>
     */
    public function ccostos(): HasMany
    {
        return $this->hasMany(Ccosto::class);
    }

    /**
     * @return HasMany<Funcionario, $this>
     */
    public function funcionarios(): HasMany
    {
        return $this->hasMany(Funcionario::class);
    }
}
