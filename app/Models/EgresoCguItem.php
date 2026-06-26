<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EgresoCguItem extends Model
{
    protected $table = 'egresos_cgu_items';

    protected $fillable = [
        'egreso_cgu_id',
        'caso_pago_proveedor_id',
        'monto',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<EgresoCgu, $this>
     */
    public function egreso(): BelongsTo
    {
        return $this->belongsTo(EgresoCgu::class, 'egreso_cgu_id');
    }

    /**
     * @return BelongsTo<CasoPagoProveedor, $this>
     */
    public function caso(): BelongsTo
    {
        return $this->belongsTo(CasoPagoProveedor::class, 'caso_pago_proveedor_id');
    }
}
