<?php

namespace App\Models\Presupuesto;

use App\Models\Catalogo;
use App\Models\Cfinanciero;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Presupuesto extends Model
{
    protected $table = 'presupuestos';

    protected $fillable = [
        'cfinanciero_id',
        'catalogo_id',
        'plan_tarea_id',
        'anio',
        'monto_asignado',
        'importacion_presupuesto_id',
    ];

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'monto_asignado' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Cfinanciero, $this>
     */
    public function cfinanciero(): BelongsTo
    {
        return $this->belongsTo(Cfinanciero::class);
    }

    /**
     * @return BelongsTo<Catalogo, $this>
     */
    public function catalogo(): BelongsTo
    {
        return $this->belongsTo(Catalogo::class);
    }

    /**
     * @return BelongsTo<PlanTarea, $this>
     */
    public function planTarea(): BelongsTo
    {
        return $this->belongsTo(PlanTarea::class);
    }

    /**
     * @return BelongsTo<ImportacionPresupuesto, $this>
     */
    public function importacionPresupuesto(): BelongsTo
    {
        return $this->belongsTo(ImportacionPresupuesto::class);
    }
}
