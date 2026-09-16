<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ConsumoBasico extends Model
{
    protected $table = 'consumos_basicos';

    protected $fillable = [
        'cliente_medidor_id',
        'caso_pago_proveedor_id',
        'numero_documento',
        'numero_medidor',
        'fecha_inicio_lectura',
        'fecha_fin_lectura',
        'fecha_emision',
        'fecha_vencimiento',
        'consumo',
        'tarifa',
        'lectura_estimada',
        'monto_neto',
        'iva',
        'monto_exento',
        'saldo_anterior',
        'monto_total',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio_lectura' => 'date:Y-m-d',
            'fecha_fin_lectura' => 'date:Y-m-d',
            'fecha_emision' => 'date:Y-m-d',
            'fecha_vencimiento' => 'date:Y-m-d',
            'consumo' => 'decimal:2',
            'lectura_estimada' => 'boolean',
            'monto_neto' => 'decimal:2',
            'iva' => 'decimal:2',
            'monto_exento' => 'decimal:2',
            'saldo_anterior' => 'decimal:2',
            'monto_total' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<ClienteMedidor, $this>
     */
    public function clienteMedidor(): BelongsTo
    {
        return $this->belongsTo(ClienteMedidor::class);
    }

    /**
     * @return BelongsTo<CasoPagoProveedor, $this>
     */
    public function casoPagoProveedor(): BelongsTo
    {
        return $this->belongsTo(CasoPagoProveedor::class);
    }

    /**
     * @return MorphMany<VinculoDocumento, $this>
     */
    public function vinculosDocumento(): MorphMany
    {
        return $this->morphMany(VinculoDocumento::class, 'vinculable');
    }
}
