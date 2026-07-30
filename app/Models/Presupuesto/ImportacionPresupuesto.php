<?php

namespace App\Models\Presupuesto;

use App\Models\SnapshotDatosExterno;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportacionPresupuesto extends Model
{
    protected $table = 'importaciones_presupuesto';

    protected $fillable = [
        'nro_version',
        'anio',
        'estado',
        'total_recibidos',
        'total_creados',
        'total_actualizados',
        'total_omitidos',
        'total_fallidos',
        'errores',
        'advertencias',
        'iniciado_en',
        'finalizado_en',
        'creado_por_user_id',
        'snapshot_datos_externo_id',
    ];

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'total_recibidos' => 'integer',
            'total_creados' => 'integer',
            'total_actualizados' => 'integer',
            'total_omitidos' => 'integer',
            'total_fallidos' => 'integer',
            'errores' => 'array',
            'advertencias' => 'array',
            'iniciado_en' => 'datetime',
            'finalizado_en' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Presupuesto, $this>
     */
    public function presupuestos(): HasMany
    {
        return $this->hasMany(Presupuesto::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por_user_id');
    }

    /**
     * @return BelongsTo<SnapshotDatosExterno, $this>
     */
    public function snapshotDatosExterno(): BelongsTo
    {
        return $this->belongsTo(SnapshotDatosExterno::class);
    }

    public function marcarComoRunning(): void
    {
        $this->update(['estado' => 'running', 'iniciado_en' => now()]);
    }

    /**
     * @param  array{total_recibidos?: int, total_creados?: int, total_actualizados?: int, total_omitidos?: int, total_fallidos?: int, errores?: ?list<string>, advertencias?: ?list<string>}  $conteos
     */
    public function marcarComoFinalizada(string $estado, array $conteos = []): void
    {
        $this->update([
            'estado' => $estado,
            'finalizado_en' => now(),
            ...$conteos,
        ]);
    }
}
