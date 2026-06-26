<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SnapshotSgfDocumento extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'snapshots_sgf_documentos';

    protected $fillable = [
        'snapshot_sgf_id',
        'documento_id',
    ];

    /**
     * @return BelongsTo<SnapshotSgf, $this>
     */
    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(SnapshotSgf::class, 'snapshot_sgf_id');
    }

    /**
     * @return BelongsTo<Documento, $this>
     */
    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }
}
