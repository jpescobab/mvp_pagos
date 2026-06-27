<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed>|null $payload_enviado
 * @property array<string, mixed>|null $payload_recibido
 */
class SolicitudApiExterna extends Model
{
    public $timestamps = false;

    protected $table = 'solicitudes_api_externas';

    protected $fillable = [
        'sistema_externo_id',
        'trabajo_integracion_id',
        'metodo_http',
        'endpoint',
        'payload_enviado',
        'payload_recibido',
        'codigo_respuesta_http',
        'estado',
        'error',
        'duracion_ms',
        'ejecutado_en',
    ];

    protected function casts(): array
    {
        return [
            'payload_enviado' => 'array',
            'payload_recibido' => 'array',
            'ejecutado_en' => 'datetime',
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
}
