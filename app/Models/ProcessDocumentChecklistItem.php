<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessDocumentChecklistItem extends Model
{
    protected $fillable = [
        'process_document_checklist_id',
        'document_requirement_id',
        'document_type_id',
        'tipo_requisito',
        'document_id',
        'estado_cumplimiento',
    ];

    /**
     * @return BelongsTo<ProcessDocumentChecklist, $this>
     */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(ProcessDocumentChecklist::class, 'process_document_checklist_id');
    }

    /**
     * @return BelongsTo<DocumentRequirement, $this>
     */
    public function requirement(): BelongsTo
    {
        return $this->belongsTo(DocumentRequirement::class, 'document_requirement_id');
    }

    /**
     * @return BelongsTo<DocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
