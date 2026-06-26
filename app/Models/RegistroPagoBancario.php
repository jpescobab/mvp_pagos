<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroPagoBancario extends Model
{
    protected $table = 'registros_pago_bancario';

    protected $fillable = [
        'caso_pago_proveedor_id',
        'numero_operacion',
        'fecha_pago',
        'monto',
        'banco',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_pago' => 'date',
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
