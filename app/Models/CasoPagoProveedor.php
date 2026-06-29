<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class CasoPagoProveedor extends Model
{
    protected $table = 'casos_pago_proveedor';

    protected $fillable = [
        'sgf_id',
        'proceso_adquisicion_id',
        'proveedor_id',
        'rut_proveedor',
        'monto',
        'sgf_status',
        'sgf_current_group_raw',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
        ];
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
     * @return MorphOne<Proceso, $this>
     */
    public function proceso(): MorphOne
    {
        return $this->morphOne(Proceso::class, 'sujeto');
    }

    /**
     * @return HasMany<Factura, $this>
     */
    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class);
    }

    /**
     * @return HasMany<RegistroContableCgu, $this>
     */
    public function registrosContablesCgu(): HasMany
    {
        return $this->hasMany(RegistroContableCgu::class);
    }

    /**
     * @return HasMany<RegistroPagoBancario, $this>
     */
    public function registrosPagoBancario(): HasMany
    {
        return $this->hasMany(RegistroPagoBancario::class);
    }

    /**
     * @return HasMany<SnapshotSgf, $this>
     */
    public function snapshotsSgf(): HasMany
    {
        return $this->hasMany(SnapshotSgf::class, 'sgf_id', 'sgf_id')->orderByDesc('id');
    }

    /**
     * @return HasMany<EgresoCguItem, $this>
     */
    public function egresoCguItems(): HasMany
    {
        return $this->hasMany(EgresoCguItem::class);
    }
}
