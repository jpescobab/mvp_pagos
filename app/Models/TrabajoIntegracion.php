<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read Carbon $iniciado_en
 * @property-read Carbon|null $finalizado_en
 */
class TrabajoIntegracion extends Model
{
    protected $table = 'trabajos_integracion';

    protected $fillable = [
        'sistema_externo_id',
        'tipo',
        'mecanismo',
        'estado',
        'iniciado_por',
        'iniciado_en',
        'finalizado_en',
        'total_elementos',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'iniciado_en' => 'datetime',
            'finalizado_en' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function iniciadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'iniciado_por');
    }

    /**
     * @return HasMany<SolicitudApiExterna, $this>
     */
    public function solicitudesApiExternas(): HasMany
    {
        return $this->hasMany(SolicitudApiExterna::class);
    }

    /**
     * @return HasMany<SnapshotDatosExterno, $this>
     */
    public function snapshotsDatosExternos(): HasMany
    {
        return $this->hasMany(SnapshotDatosExterno::class);
    }

    /**
     * @return HasMany<EjecucionAutomatizacionNavegador, $this>
     */
    public function ejecucionesAutomatizacionNavegador(): HasMany
    {
        return $this->hasMany(EjecucionAutomatizacionNavegador::class);
    }

    /**
     * Minutos de inactividad tolerados antes de tratar este trabajo como
     * huérfano (ver config/integraciones.php).
     */
    public function umbralHuerfanoEnMinutos(): int
    {
        $umbrales = config('integraciones.umbral_huerfano_minutos');

        return $umbrales[$this->tipo] ?? $umbrales['default'];
    }

    /**
     * Un trabajo en "en_progreso" cuyo iniciado_en superó su umbral se
     * considera huérfano: el proceso que lo ejecutaba probablemente murió
     * sin poder reportar ni éxito ni error.
     */
    public function esHuerfano(): bool
    {
        return $this->estado === 'en_progreso'
            && $this->iniciado_en->lt(now()->subMinutes($this->umbralHuerfanoEnMinutos()));
    }
}
