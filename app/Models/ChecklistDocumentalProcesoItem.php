<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistDocumentalProcesoItem extends Model
{
    protected $fillable = [
        'checklist_documental_proceso_id',
        'requisito_documental_id',
        'tipo_documento_id',
        'tipo_requisito',
        'documento_id',
        'estado_cumplimiento',
    ];

    /**
     * @return BelongsTo<ChecklistDocumentalProceso, $this>
     */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(ChecklistDocumentalProceso::class, 'checklist_documental_proceso_id');
    }

    /**
     * @return BelongsTo<RequisitoDocumental, $this>
     */
    public function requisito(): BelongsTo
    {
        return $this->belongsTo(RequisitoDocumental::class, 'requisito_documental_id');
    }

    /**
     * @return BelongsTo<TipoDocumento, $this>
     */
    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumento::class);
    }

    /**
     * @return BelongsTo<Documento, $this>
     */
    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }
}
