<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ProcesoAdquisicion extends Model
{
    protected $table = 'procesos_adquisicion';

    protected $fillable = [
        'codigo',
        'modalidad_id',
        'ccosto_id',
        'proveedor_id',
        'monto',
        'objeto',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<ModalidadAdquisicion, $this>
     */
    public function modalidad(): BelongsTo
    {
        return $this->belongsTo(ModalidadAdquisicion::class, 'modalidad_id');
    }

    /**
     * @return BelongsTo<Ccosto, $this>
     */
    public function ccosto(): BelongsTo
    {
        return $this->belongsTo(Ccosto::class);
    }

    /**
     * @return BelongsTo<Proveedor, $this>
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    /**
     * @return MorphOne<Proceso, $this>
     */
    public function proceso(): MorphOne
    {
        return $this->morphOne(Proceso::class, 'sujeto');
    }

    /**
     * @return HasMany<CasoPagoProveedor, $this>
     */
    public function casosPagoProveedor(): HasMany
    {
        return $this->hasMany(CasoPagoProveedor::class);
    }
}
