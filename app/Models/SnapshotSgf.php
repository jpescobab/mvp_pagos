<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property array<string, mixed> $payload_crudo
 * @property array<string, mixed> $payload_normalizado
 */
class SnapshotSgf extends Model
{
    public $timestamps = false;

    protected $table = 'snapshots_sgf';

    protected $fillable = [
        'importacion_sgf_id',
        'sgf_id',
        'payload_crudo',
        'payload_normalizado',
        'hash',
        'capturado_en',
    ];

    protected function casts(): array
    {
        return [
            'payload_crudo' => 'array',
            'payload_normalizado' => 'array',
            'capturado_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ImportacionSgf, $this>
     */
    public function importacion(): BelongsTo
    {
        return $this->belongsTo(ImportacionSgf::class, 'importacion_sgf_id');
    }

    /**
     * @return HasMany<SnapshotSgfDocumento, $this>
     */
    public function documentos(): HasMany
    {
        return $this->hasMany(SnapshotSgfDocumento::class);
    }
}
