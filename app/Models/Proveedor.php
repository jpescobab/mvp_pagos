<?php

namespace App\Models;

use App\Models\Concerns\RegistraAuditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proveedor extends Model
{
    use RegistraAuditoria;
    use SoftDeletes;

    /** Registro identificado que todavía no está habilitado para operar. */
    public const ESTADO_BORRADOR = 'borrador';

    public const ESTADO_ACTIVO = 'activo';

    /** Estuvo habilitado y se dio de baja. */
    public const ESTADO_INACTIVO = 'inactivo';

    /** @var list<string> */
    public const ESTADOS = [self::ESTADO_BORRADOR, self::ESTADO_ACTIVO, self::ESTADO_INACTIVO];

    protected $table = 'proveedores';

    protected $fillable = [
        'rutproveedor',
        'nombre',
        'correo',
        'direccion',
        'contacto',
        'imagen',
        'estado',
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
            'rubros' => 'array',
        ];
    }

    /**
     * Proveedores disponibles para operar. El filtro vive acá y no como un
     * `where` repetido en cada controlador: que no existiera como concepto es
     * la razón por la que los selectores nacieron sin filtrar.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_ACTIVO);
    }

    /**
     * Normaliza el RUT al guardarlo (sin puntos, con guión, dígito verificador
     * en mayúscula) para que dos formatos del mismo RUT nunca generen
     * proveedores duplicados, sin importar el origen (formulario manual,
     * importación SGF, consulta a Mercado Público, etc.).
     *
     * @return Attribute<string, string>
     */
    protected function rutproveedor(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => self::normalizarRut($value),
        );
    }

    public static function normalizarRut(string $rut): string
    {
        $limpio = strtoupper(preg_replace('/[^0-9kK]/', '', $rut) ?? '');

        if ($limpio === '') {
            return '';
        }

        return substr($limpio, 0, -1).'-'.substr($limpio, -1);
    }

    /**
     * @return HasMany<ClienteMedidor, $this>
     */
    public function clientesMedidores(): HasMany
    {
        return $this->hasMany(ClienteMedidor::class);
    }
}
