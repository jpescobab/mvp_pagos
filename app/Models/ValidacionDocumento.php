<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidacionDocumento extends Model
{
    protected $table = 'validaciones_documento';

    public const UPDATED_AT = null;

    protected $fillable = [
        'documento_id',
        'estado',
        'observacion',
        'validado_por',
        'validado_en',
    ];

    protected function casts(): array
    {
        return [
            'validado_en' => 'datetime',
        ];
    }

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
    public function validadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validado_por');
    }
}
