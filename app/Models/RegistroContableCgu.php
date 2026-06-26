<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroContableCgu extends Model
{
    protected $table = 'registros_contables_cgu';

    protected $fillable = [
        'caso_pago_proveedor_id',
        'numero_registro',
        'fecha_registro',
        'monto',
        'observaciones',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_registro' => 'date',
            'monto' => 'decimal:2',
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
     * @return BelongsTo<User, $this>
     */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
