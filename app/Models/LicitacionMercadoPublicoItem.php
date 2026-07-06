<?php

namespace App\Models;

use Database\Factories\LicitacionMercadoPublicoItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed>|null $adjudicacion
 */
class LicitacionMercadoPublicoItem extends Model
{
    /** @use HasFactory<LicitacionMercadoPublicoItemFactory> */
    use HasFactory;

    protected $table = 'licitacion_mercado_publico_items';

    protected $fillable = [
        'licitacion_mercado_publico_id',
        'correlativo',
        'codigo_producto',
        'categoria',
        'nombre_producto',
        'descripcion',
        'unidad_medida',
        'cantidad',
        'adjudicacion',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'adjudicacion' => 'array',
        ];
    }

    /**
     * @return BelongsTo<LicitacionMercadoPublico, $this>
     */
    public function licitacionMercadoPublico(): BelongsTo
    {
        return $this->belongsTo(LicitacionMercadoPublico::class);
    }
}
