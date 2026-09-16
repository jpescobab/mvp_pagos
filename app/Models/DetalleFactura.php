<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleFactura extends Model
{
    protected $table = 'detalles_factura';

    protected $fillable = [
        'factura_id',
        'tipo_compra_id',
        'ccosto_id',
        'cantidad',
        'unidad_medida',
        'monto',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'monto' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Factura, $this>
     */
    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }

    /**
     * @return BelongsTo<TipoCompra, $this>
     */
    public function tipoCompra(): BelongsTo
    {
        return $this->belongsTo(TipoCompra::class);
    }

    /**
     * @return BelongsTo<Ccosto, $this>
     */
    public function ccosto(): BelongsTo
    {
        return $this->belongsTo(Ccosto::class);
    }
}
