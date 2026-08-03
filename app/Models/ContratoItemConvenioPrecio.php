<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContratoItemConvenioPrecio extends Model
{
    protected $table = 'contrato_items_convenio_precio';

    protected $fillable = [
        'contrato_id',
        'descripcion',
        'unidad_medida',
        'precio_unitario',
        'moneda',
        'vigente_desde',
        'vigente_hasta',
    ];

    protected function casts(): array
    {
        return [
            'precio_unitario' => 'decimal:2',
            'vigente_desde' => 'date:Y-m-d',
            'vigente_hasta' => 'date:Y-m-d',
        ];
    }

    /**
     * @return BelongsTo<Contrato, $this>
     */
    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }
}
