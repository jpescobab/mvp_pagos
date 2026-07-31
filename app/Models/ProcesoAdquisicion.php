<?php

namespace App\Models;

use App\Models\Presupuesto\CertificadoDisponibilidadPresupuestaria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ProcesoAdquisicion extends Model
{
    protected $table = 'procesos_adquisicion';

    protected $fillable = [
        'codigo',
        'fecha_inicio',
        'nombre',
        'id_requerimiento',
        'modalidad_id',
        'ccosto_id',
        'funcionario_requirente_id',
        'proveedor_id',
        'caracteristicas',
        'motivo_contratacion',
        'en_plan_compras',
        'id_pac',
        'codigo_bip',
        'monto_estimado',
        'moneda_compra',
        'monto_estimado_solicitado',
        'fecha_paridad',
        'paridad',
        'objeto',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date:Y-m-d',
            'en_plan_compras' => 'boolean',
            'monto_estimado' => 'decimal:2',
            'monto_estimado_solicitado' => 'decimal:4',
            'fecha_paridad' => 'date:Y-m-d',
            'paridad' => 'decimal:4',
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
     * @return BelongsTo<Funcionario, $this>
     */
    public function funcionarioRequirente(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class, 'funcionario_requirente_id');
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

    /**
     * @return HasMany<OrdenCompraMercadoPublico, $this>
     */
    public function ordenesCompraMercadoPublico(): HasMany
    {
        return $this->hasMany(OrdenCompraMercadoPublico::class);
    }

    /**
     * @return HasMany<LicitacionMercadoPublico, $this>
     */
    public function licitacionesMercadoPublico(): HasMany
    {
        return $this->hasMany(LicitacionMercadoPublico::class);
    }

    /**
     * @return HasMany<CertificadoDisponibilidadPresupuestaria, $this>
     */
    public function cdps(): HasMany
    {
        return $this->hasMany(CertificadoDisponibilidadPresupuestaria::class);
    }
}
