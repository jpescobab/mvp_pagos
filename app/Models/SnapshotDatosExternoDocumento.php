<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SnapshotDatosExternoDocumento extends Model
{
    public $timestamps = false;

    protected $table = 'snapshots_datos_externos_documentos';

    protected $fillable = [
        'snapshot_datos_externo_id',
        'documento_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SnapshotDatosExterno, $this>
     */
    public function snapshotDatosExterno(): BelongsTo
    {
        return $this->belongsTo(SnapshotDatosExterno::class);
    }

    /**
     * @return BelongsTo<Documento, $this>
     */
    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }
}
