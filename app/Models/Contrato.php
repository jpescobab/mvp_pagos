<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Contrato extends Model
{
    protected $table = 'contratos';

    protected $fillable = [
        'codigo',
        'id_institucional',
        'modalidad_compra',
        'id_proceso_mp',
        'tipo_contrato',
        'referencia',
        'fecha_inicio_vigencia',
        'fecha_fin_vigencia',
        'proveedor_id',
        'materia',
        'submateria',
        'tiene_convenio_precio',
        'tiene_calendario_pago',
        'periodicidad_pago',
        'monto_total',
        'proceso_adquisicion_id',
        'licitacion_mercado_publico_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio_vigencia' => 'date:Y-m-d',
            'fecha_fin_vigencia' => 'date:Y-m-d',
            'tiene_convenio_precio' => 'boolean',
            'tiene_calendario_pago' => 'boolean',
            'monto_total' => 'decimal:2',
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
     * @return BelongsTo<LicitacionMercadoPublico, $this>
     */
    public function licitacionMercadoPublico(): BelongsTo
    {
        return $this->belongsTo(LicitacionMercadoPublico::class);
    }

    /**
     * @return MorphOne<Proceso, $this>
     */
    public function proceso(): MorphOne
    {
        return $this->morphOne(Proceso::class, 'sujeto');
    }

    /**
     * @return HasMany<ContratoItemConvenioPrecio, $this>
     */
    public function itemsConvenioPrecio(): HasMany
    {
        return $this->hasMany(ContratoItemConvenioPrecio::class);
    }

    /**
     * @return HasMany<ContratoCuota, $this>
     */
    public function cuotas(): HasMany
    {
        return $this->hasMany(ContratoCuota::class)->orderBy('numero_cuota');
    }

    /**
     * @return HasMany<OrdenCompraMercadoPublico, $this>
     */
    public function ordenesCompraMercadoPublico(): HasMany
    {
        return $this->hasMany(OrdenCompraMercadoPublico::class);
    }
}
