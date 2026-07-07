<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property array<string, mixed> $payload_crudo
 * @property array<string, mixed>|null $payload_normalizado
 */
class SnapshotDatosExterno extends Model
{
    public $timestamps = false;

    protected $table = 'snapshots_datos_externos';

    protected $fillable = [
        'sistema_externo_id',
        'trabajo_integracion_id',
        'solicitud_api_externa_id',
        'metodo_captura',
        'referencia_externa',
        'payload_crudo',
        'payload_normalizado',
        'hash',
        'capturado_en',
        'capturado_por',
        'vinculable_type',
        'vinculable_id',
    ];

    protected function casts(): array
    {
        return [
            'payload_crudo' => 'array',
            'payload_normalizado' => 'array',
            'capturado_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SistemaExterno, $this>
     */
    public function sistemaExterno(): BelongsTo
    {
        return $this->belongsTo(SistemaExterno::class);
    }

    /**
     * @return BelongsTo<TrabajoIntegracion, $this>
     */
    public function trabajoIntegracion(): BelongsTo
    {
        return $this->belongsTo(TrabajoIntegracion::class);
    }

    /**
     * @return BelongsTo<SolicitudApiExterna, $this>
     */
    public function solicitudApiExterna(): BelongsTo
    {
        return $this->belongsTo(SolicitudApiExterna::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function capturadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'capturado_por');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function vinculable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<SnapshotDatosExternoDocumento, $this>
     */
    public function documentos(): HasMany
    {
        return $this->hasMany(SnapshotDatosExternoDocumento::class);
    }
}
