<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerfilAutenticacionNavegador extends Model
{
    protected $table = 'perfiles_autenticacion_navegador';

    protected $fillable = [
        'conector_automatizacion_navegador_id',
        'nombre',
        'almacen_secreto',
        'referencia_secreto',
        'activo',
        'creado_por',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
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
     * @return BelongsTo<User, $this>
     */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}
