<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proveedor extends Model
{
    use SoftDeletes;

    protected $table = 'proveedores';

    protected $fillable = [
        'rutproveedor',
        'nombre',
        'correo',
        'direccion',
        'contacto',
        'imagen',
        'activo',
        'giro',
        'tipo_contribuyente',
        'rubros',
        'contacto_cargo',
        'contacto_telefono',
        'region',
        'comuna',
        'banco',
        'tipo_cuenta',
        'numero_cuenta',
        'condicion_pago',
        'moneda',
        'correo_pago',
        'documento_respaldo_path',
        'notas_internas',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'rubros' => 'array',
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
