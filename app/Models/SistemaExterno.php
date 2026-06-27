<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SistemaExterno extends Model
{
    protected $table = 'sistemas_externos';

    protected $fillable = [
        'codigo',
        'nombre',
        'tipo_integracion',
        'activo',
        'url_base',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * @return HasMany<TrabajoIntegracion, $this>
     */
    public function trabajosIntegracion(): HasMany
    {
        return $this->hasMany(TrabajoIntegracion::class);
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
     * @return HasMany<ConectorAutomatizacionNavegador, $this>
     */
    public function conectoresAutomatizacionNavegador(): HasMany
    {
        return $this->hasMany(ConectorAutomatizacionNavegador::class);
    }
}
