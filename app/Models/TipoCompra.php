<?php

namespace App\Models;

use App\Models\Concerns\RegistraAuditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoCompra extends Model
{
    use RegistraAuditoria;

    protected $table = 'tipos_compra';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * @return HasMany<DetalleFactura, $this>
     */
    public function detallesFactura(): HasMany
    {
        return $this->hasMany(DetalleFactura::class);
    }
}
