<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessDocumentChecklist extends Model
{
    protected $fillable = [
        'process_id',
        'document_requirement_set_id',
        'generated_at',
        'generated_by',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Process, $this>
     */
    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    /**
     * @return BelongsTo<DocumentRequirementSet, $this>
     */
    public function requirementSet(): BelongsTo
    {
        return $this->belongsTo(DocumentRequirementSet::class, 'document_requirement_set_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * @return HasMany<ProcessDocumentChecklistItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProcessDocumentChecklistItem::class);
    }
}
