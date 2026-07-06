<?php

namespace App\Models;

use Database\Factories\OrdenCompraMercadoPublicoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property array<string, mixed>|null $organismo_comprador
 * @property array<int, array<string, mixed>>|null $cronograma
 */
class OrdenCompraMercadoPublico extends Model
{
    /** @use HasFactory<OrdenCompraMercadoPublicoFactory> */
    use HasFactory;

    protected $table = 'ordenes_compra_mercado_publico';

    protected $fillable = [
        'codigo',
        'proveedor_id',
        'proceso_adquisicion_id',
        'snapshot_datos_externo_id',
        'estado_mercado_publico',
        'moneda',
        'forma_pago',
        'plazo_entrega_dias',
        'monto_neto',
        'monto_total',
        'fecha_emision',
        'organismo_comprador',
        'cronograma',
    ];

    protected function casts(): array
    {
        return [
            'monto_neto' => 'decimal:2',
            'monto_total' => 'decimal:2',
            'fecha_emision' => 'date',
            'organismo_comprador' => 'array',
            'cronograma' => 'array',
        ];
    }

    /**
     * @return HasMany<OrdenCompraMercadoPublicoItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrdenCompraMercadoPublicoItem::class);
    }

    /**
     * @return BelongsTo<Proveedor, $this>
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    /**
     * @return BelongsTo<ProcesoAdquisicion, $this>
     */
    public function procesoAdquisicion(): BelongsTo
    {
        return $this->belongsTo(ProcesoAdquisicion::class);
    }

    /**
     * @return BelongsTo<SnapshotDatosExterno, $this>
     */
    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(SnapshotDatosExterno::class, 'snapshot_datos_externo_id');
    }
}
