<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistDocumentalProceso extends Model
{
    protected $table = 'checklists_documentales_proceso';

    protected $fillable = [
        'proceso_id',
        'conjunto_requisitos_documentales_id',
        'generado_en',
        'generado_por',
    ];

    protected function casts(): array
    {
        return [
            'generado_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Proceso, $this>
     */
    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class);
    }

    /**
     * @return BelongsTo<ConjuntoRequisitosDocumentales, $this>
     */
    public function conjuntoRequisitos(): BelongsTo
    {
        return $this->belongsTo(ConjuntoRequisitosDocumentales::class, 'conjunto_requisitos_documentales_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function generadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por');
    }

    /**
     * @return HasMany<ChecklistDocumentalProcesoItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ChecklistDocumentalProcesoItem::class);
    }
}
