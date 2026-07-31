<?php

namespace App\Models\Presupuesto;

use App\Models\Cfinanciero;
use App\Models\Proceso;
use App\Models\ProcesoAdquisicion;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class CertificadoDisponibilidadPresupuestaria extends Model
{
    protected $table = 'certificados_disponibilidad_presupuestaria';

    protected $fillable = [
        'folio',
        'presupuesto_id',
        'cfinanciero_id',
        'denominacion',
        'unidad_ejecutora',
        'n_ue',
        'subtitulo',
        'tipo_gasto',
        'codigo_iniciativa',
        'nombre',
        'nombre_iniciativa',
        'programa_presupuestario',
        'caracter_gasto',
        'medio_solicitud',
        'fecha_solicitud',
        'moneda_compra',
        'total_moneda_compra',
        'fecha_paridad',
        'paridad',
        'monto',
        'anio_validez',
        'requerimiento_numero',
        'mercado_publico_tipo',
        'mercado_publico_id',
        'proceso_adquisicion_id',
        'saldo_disponible_al_emitir',
        'hubo_sobregiro_al_emitir',
        'cdp_original_id',
        'firmado_por',
        'firmado_en',
    ];

    protected function casts(): array
    {
        return [
            'fecha_solicitud' => 'date:Y-m-d',
            'total_moneda_compra' => 'decimal:4',
            'fecha_paridad' => 'date:Y-m-d',
            'paridad' => 'decimal:4',
            'monto' => 'decimal:2',
            'anio_validez' => 'integer',
            'saldo_disponible_al_emitir' => 'decimal:2',
            'hubo_sobregiro_al_emitir' => 'boolean',
            'firmado_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Presupuesto, $this>
     */
    public function presupuesto(): BelongsTo
    {
        return $this->belongsTo(Presupuesto::class);
    }

    /**
     * @return BelongsTo<Cfinanciero, $this>
     */
    public function cfinanciero(): BelongsTo
    {
        return $this->belongsTo(Cfinanciero::class);
    }

    /**
     * @return BelongsTo<ProcesoAdquisicion, $this>
     */
    public function procesoAdquisicion(): BelongsTo
    {
        return $this->belongsTo(ProcesoAdquisicion::class);
    }

    /**
     * @return BelongsTo<CertificadoDisponibilidadPresupuestaria, $this>
     */
    public function cdpOriginal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'cdp_original_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function firmadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'firmado_por');
    }

    /**
     * @return MorphOne<Proceso, $this>
     */
    public function proceso(): MorphOne
    {
        return $this->morphOne(Proceso::class, 'sujeto');
    }
}
