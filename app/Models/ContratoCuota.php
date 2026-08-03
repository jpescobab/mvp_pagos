<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property-read Carbon $fecha_vencimiento
 */
class ContratoCuota extends Model
{
    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_PAGADA = 'pagada';

    protected $table = 'contrato_cuotas';

    protected $fillable = [
        'contrato_id',
        'numero_cuota',
        'fecha_vencimiento',
        'monto',
        'moneda',
        'estado',
        'caso_pago_proveedor_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_vencimiento' => 'date:Y-m-d',
            'monto' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Contrato, $this>
     */
    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    /**
     * @return BelongsTo<CasoPagoProveedor, $this>
     */
    public function casoPagoProveedor(): BelongsTo
    {
        return $this->belongsTo(CasoPagoProveedor::class);
    }

    /**
     * Estado "vencida" derivado en el momento de la consulta — nunca
     * persistido, para no requerir un job programado que lo mantenga
     * sincronizado (ver design.md del change modulo-contratos).
     *
     * @return Attribute<bool, never>
     */
    protected function estaVencida(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->estado === self::ESTADO_PENDIENTE
                && $this->fecha_vencimiento->isPast(),
        );
    }
}
