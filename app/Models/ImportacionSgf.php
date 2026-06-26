<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportacionSgf extends Model
{
    protected $table = 'importaciones_sgf';

    protected $fillable = [
        'fuente',
        'iniciado_por',
        'iniciado_en',
        'finalizado_en',
        'total_filas',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'iniciado_en' => 'datetime',
            'finalizado_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function iniciadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'iniciado_por');
    }

    /**
     * @return HasMany<SnapshotSgf, $this>
     */
    public function snapshots(): HasMany
    {
        return $this->hasMany(SnapshotSgf::class);
    }
}
