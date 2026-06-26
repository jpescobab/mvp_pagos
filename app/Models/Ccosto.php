<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ccosto extends Model
{
    protected $table = 'ccostos';

    protected $fillable = ['cfinanciero_id', 'codigo', 'nombre', 'cod_edificio', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Cfinanciero, $this>
     */
    public function cfinanciero(): BelongsTo
    {
        return $this->belongsTo(Cfinanciero::class);
    }
}
