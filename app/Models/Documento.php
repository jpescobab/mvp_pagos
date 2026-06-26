<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Documento extends Model
{
    protected $fillable = ['tipo_documento_id', 'titulo', 'subido_por'];

    /**
     * @return BelongsTo<TipoDocumento, $this>
     */
    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumento::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    /**
     * @return HasMany<VersionDocumento, $this>
     */
    public function versiones(): HasMany
    {
        return $this->hasMany(VersionDocumento::class);
    }

    /**
     * @return HasMany<VinculoDocumento, $this>
     */
    public function vinculos(): HasMany
    {
        return $this->hasMany(VinculoDocumento::class);
    }

    /**
     * @return HasMany<ValidacionDocumento, $this>
     */
    public function validaciones(): HasMany
    {
        return $this->hasMany(ValidacionDocumento::class);
    }

    public function estadoVigente(): string
    {
        $ultima = $this->validaciones->sortByDesc('id')->first();

        if ($ultima === null) {
            return 'pendiente';
        }

        return $ultima->estado;
    }
}
