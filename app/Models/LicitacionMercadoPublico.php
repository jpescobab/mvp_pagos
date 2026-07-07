<?php

namespace App\Models;

use Database\Factories\LicitacionMercadoPublicoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property array<string, mixed>|null $organismo_comprador
 * @property array<int, array<string, mixed>>|null $cronograma
 * @property array<string, mixed>|null $adjudicacion
 */
class LicitacionMercadoPublico extends Model
{
    /** @use HasFactory<LicitacionMercadoPublicoFactory> */
    use HasFactory;

    protected $table = 'licitaciones_mercado_publico';

    protected $fillable = [
        'codigo',
        'nombre',
        'proceso_adquisicion_id',
        'snapshot_datos_externo_id',
        'estado_mercado_publico',
        'codigo_estado_mercado_publico',
        'moneda',
        'monto_estimado',
        'organismo_comprador',
        'cronograma',
        'adjudicacion',
    ];

    protected function casts(): array
    {
        return [
            'monto_estimado' => 'decimal:2',
            'organismo_comprador' => 'array',
            'cronograma' => 'array',
            'adjudicacion' => 'array',
        ];
    }

    /**
     * @return HasMany<LicitacionMercadoPublicoItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(LicitacionMercadoPublicoItem::class);
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
