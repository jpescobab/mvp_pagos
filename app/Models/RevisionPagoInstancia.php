<?php

namespace App\Models;

use App\Enums\PagoProveedores\InstanciaRevision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property InstanciaRevision $instancia
 * @property bool $totales_verificados
 */
class RevisionPagoInstancia extends Model
{
    protected $table = 'revisiones_pago_instancia';

    protected $fillable = [
        'caso_pago_proveedor_id',
        'instancia',
        'totales_verificados',
        'verificado_por',
        'verificado_en',
    ];

    protected function casts(): array
    {
        return [
            'instancia' => InstanciaRevision::class,
            'totales_verificados' => 'boolean',
            'verificado_en' => 'datetime',
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
    public function verificadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verificado_por');
    }
}
