<?php

namespace App\Models;

use Database\Factories\OrdenCompraMercadoPublicoItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenCompraMercadoPublicoItem extends Model
{
    /** @use HasFactory<OrdenCompraMercadoPublicoItemFactory> */
    use HasFactory;

    protected $table = 'orden_compra_mercado_publico_items';

    protected $fillable = [
        'orden_compra_mercado_publico_id',
        'codigo_producto',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'monto_total',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'precio_unitario' => 'decimal:2',
            'monto_total' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<OrdenCompraMercadoPublico, $this>
     */
    public function ordenCompraMercadoPublico(): BelongsTo
    {
        return $this->belongsTo(OrdenCompraMercadoPublico::class);
    }
}
