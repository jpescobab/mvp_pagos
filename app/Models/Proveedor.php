<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proveedor extends Model
{
    use SoftDeletes;

    protected $table = 'proveedores';

    protected $fillable = ['rutproveedor', 'nombre', 'correo', 'direccion', 'contacto', 'imagen', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ClienteMedidor, $this>
     */
    public function clientesMedidores(): HasMany
    {
        return $this->hasMany(ClienteMedidor::class);
    }
}
