<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentValidation extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'document_id',
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
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function validadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validado_por');
    }
}
