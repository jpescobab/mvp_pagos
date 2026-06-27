<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EjecucionAutomatizacionNavegador extends Model
{
    protected $table = 'ejecuciones_automatizacion_navegador';

    protected $fillable = [
        'conector_automatizacion_navegador_id',
        'perfil_autenticacion_navegador_id',
        'trabajo_integracion_id',
        'iniciado_por',
        'estado',
        'iniciado_en',
        'finalizado_en',
        'resumen_resultado',
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
     * @return BelongsTo<ConectorAutomatizacionNavegador, $this>
     */
    public function conectorAutomatizacionNavegador(): BelongsTo
    {
        return $this->belongsTo(ConectorAutomatizacionNavegador::class);
    }

    /**
     * @return BelongsTo<PerfilAutenticacionNavegador, $this>
     */
    public function perfilAutenticacionNavegador(): BelongsTo
    {
        return $this->belongsTo(PerfilAutenticacionNavegador::class);
    }

    /**
     * @return BelongsTo<TrabajoIntegracion, $this>
     */
    public function trabajoIntegracion(): BelongsTo
    {
        return $this->belongsTo(TrabajoIntegracion::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function iniciadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'iniciado_por');
    }

    /**
     * @return HasMany<PasoAutomatizacionNavegador, $this>
     */
    public function pasos(): HasMany
    {
        return $this->hasMany(PasoAutomatizacionNavegador::class);
    }

    /**
     * @return HasMany<ArtefactoAutomatizacionNavegador, $this>
     */
    public function artefactos(): HasMany
    {
        return $this->hasMany(ArtefactoAutomatizacionNavegador::class);
    }
}
