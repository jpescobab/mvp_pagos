<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $fillable = ['document_type_id', 'titulo', 'uploaded_by'];

    /**
     * @return BelongsTo<DocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @return HasMany<DocumentVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    /**
     * @return HasMany<DocumentLink, $this>
     */
    public function links(): HasMany
    {
        return $this->hasMany(DocumentLink::class);
    }

    /**
     * @return HasMany<DocumentValidation, $this>
     */
    public function validations(): HasMany
    {
        return $this->hasMany(DocumentValidation::class);
    }

    public function estadoVigente(): string
    {
        $ultima = $this->validations->sortByDesc('id')->first();

        if ($ultima === null) {
            return 'pendiente';
        }

        return $ultima->estado;
    }
}
