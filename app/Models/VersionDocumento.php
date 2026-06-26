<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VersionDocumento extends Model
{
    protected $table = 'versiones_documento';

    public const UPDATED_AT = null;

    protected $fillable = [
        'documento_id',
        'numero_version',
        'ruta_archivo',
        'nombre_archivo',
        'tipo_mime',
        'tamano_bytes',
        'hash',
        'subido_por',
    ];

    /**
     * @return BelongsTo<Documento, $this>
     */
    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }
}
