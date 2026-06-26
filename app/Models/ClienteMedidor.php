<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClienteMedidor extends Model
{
    use SoftDeletes;

    protected $table = 'clientes_medidores';

    protected $fillable = [
        'numero_cliente',
        'proveedor_id',
        'ccosto_id',
        'tipo_suministro',
        'direccion_suministro',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Proveedor, $this>
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    /**
     * @return BelongsTo<Ccosto, $this>
     */
    public function ccosto(): BelongsTo
    {
        return $this->belongsTo(Ccosto::class);
    }
}
