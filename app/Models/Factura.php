<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Factura extends Model
{
    protected $table = 'facturas';

    protected $fillable = [
        'caso_pago_proveedor_id',
        'proveedor_id',
        'folio',
        'monto',
        'fecha_emision',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha_emision' => 'date',
        ];
    }

    /**
     * @return BelongsTo<CasoPagoProveedor, $this>
     */
    public function caso(): BelongsTo
    {
        return $this->belongsTo(CasoPagoProveedor::class, 'caso_pago_proveedor_id');
    }

    /**
     * @return BelongsTo<Proveedor, $this>
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }
}
