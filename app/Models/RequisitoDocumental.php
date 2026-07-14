<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitoDocumental extends Model
{
    protected $table = 'requisitos_documentales';

    protected $fillable = [
        'conjunto_requisitos_documentales_id',
        'tipo_documento_id',
        'definicion_workflow_id',
        'modalidad_id',
        'tipo_proceso_pago_id',
        'estado_workflow_id',
        'monto_desde',
        'monto_hasta',
        'tipo_requisito',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'monto_desde' => 'decimal:2',
            'monto_hasta' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ConjuntoRequisitosDocumentales, $this>
     */
    public function conjuntoRequisitos(): BelongsTo
    {
        return $this->belongsTo(ConjuntoRequisitosDocumentales::class, 'conjunto_requisitos_documentales_id');
    }

    /**
     * @return BelongsTo<TipoDocumento, $this>
     */
    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumento::class);
    }

    /**
     * @return BelongsTo<DefinicionWorkflow, $this>
     */
    public function definicionWorkflow(): BelongsTo
    {
        return $this->belongsTo(DefinicionWorkflow::class);
    }

    /**
     * @return BelongsTo<ModalidadAdquisicion, $this>
     */
    public function modalidad(): BelongsTo
    {
        return $this->belongsTo(ModalidadAdquisicion::class, 'modalidad_id');
    }

    /**
     * @return BelongsTo<TipoProcesoPago, $this>
     */
    public function tipoProcesoPago(): BelongsTo
    {
        return $this->belongsTo(TipoProcesoPago::class, 'tipo_proceso_pago_id');
    }

    /**
     * @return BelongsTo<EstadoWorkflow, $this>
     */
    public function estadoWorkflow(): BelongsTo
    {
        return $this->belongsTo(EstadoWorkflow::class);
    }
}
