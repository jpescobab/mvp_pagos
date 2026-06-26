<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstadoWorkflow extends Model
{
    protected $table = 'estados_workflow';

    protected $fillable = ['definicion_workflow_id', 'codigo', 'nombre', 'es_inicial', 'es_final'];

    protected function casts(): array
    {
        return [
            'es_inicial' => 'boolean',
            'es_final' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<DefinicionWorkflow, $this>
     */
    public function definicionWorkflow(): BelongsTo
    {
        return $this->belongsTo(DefinicionWorkflow::class);
    }
}
