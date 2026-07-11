<?php

namespace App\Models;

use App\Enums\PagoProveedores\InstanciaRevision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $estado
 * @property InstanciaRevision|null $instancia
 * @property string|null $observacion
 */
class ValidacionDocumento extends Model
{
    protected $table = 'validaciones_documento';

    public const UPDATED_AT = null;

    protected $fillable = [
        'documento_id',
        'estado',
        'instancia',
        'observacion',
        'validado_por',
        'validado_en',
    ];

    protected function casts(): array
    {
        return [
            'instancia' => InstanciaRevision::class,
            'validado_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Documento, $this>
     */
    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function validadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validado_por');
    }
}
