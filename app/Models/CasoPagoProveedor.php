<?php

namespace App\Models;

use App\Services\PagoProveedores\CfinancieroPorDefectoResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @property-read Proveedor|null $proveedor
 * @property-read Proceso|null $proceso
 * @property-read ProcesoAdquisicion|null $procesoAdquisicion
 */
class CasoPagoProveedor extends Model
{
    protected $table = 'casos_pago_proveedor';

    protected $fillable = [
        'sgf_id',
        'proceso_adquisicion_id',
        'proveedor_id',
        'rut_proveedor',
        'monto',
        'sgf_status',
        'sgf_current_group_raw',
        'periodo',
        'observacion',
        'folio_egreso',
        'numero',
        'fecha_sii',
        'observacion_egreso',
        'sgf_numero_traspaso',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha_sii' => 'date',
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
     * @return BelongsTo<ProcesoAdquisicion, $this>
     */
    public function procesoAdquisicion(): BelongsTo
    {
        return $this->belongsTo(ProcesoAdquisicion::class);
    }

    /**
     * @return MorphOne<Proceso, $this>
     */
    public function proceso(): MorphOne
    {
        return $this->morphOne(Proceso::class, 'sujeto');
    }

    /**
     * @return HasMany<Factura, $this>
     */
    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class);
    }

    /**
     * @return HasMany<RegistroContableCgu, $this>
     */
    public function registrosContablesCgu(): HasMany
    {
        return $this->hasMany(RegistroContableCgu::class);
    }

    /**
     * @return HasMany<RegistroPagoBancario, $this>
     */
    public function registrosPagoBancario(): HasMany
    {
        return $this->hasMany(RegistroPagoBancario::class);
    }

    /**
     * @return HasMany<SnapshotDatosExterno, $this>
     */
    public function snapshotsSgf(): HasMany
    {
        return $this->hasMany(SnapshotDatosExterno::class, 'referencia_externa', 'sgf_id')
            ->whereIn('sistema_externo_id', SistemaExterno::query()->where('codigo', 'SGF')->select('id'))
            ->orderByDesc('id');
    }

    /**
     * @return HasMany<EgresoCguItem, $this>
     */
    public function egresoCguItems(): HasMany
    {
        return $this->hasMany(EgresoCguItem::class);
    }

    /**
     * @return HasMany<RevisionPagoInstancia, $this>
     */
    public function revisionesInstancia(): HasMany
    {
        return $this->hasMany(RevisionPagoInstancia::class);
    }

    /**
     * Centro financiero del caso, derivado del proceso de adquisición vinculado
     * (caso -> proceso_adquisicion -> ccosto -> cfinanciero). Si el caso no
     * tiene proceso_adquisicion vinculado, cae al cfinanciero por defecto
     * configurado (ver CfinancieroPorDefectoResolver); null si tampoco hay
     * default resoluble.
     */
    public function cfinancieroId(): ?int
    {
        return $this->procesoAdquisicion?->ccosto->cfinanciero_id
            ?? app(CfinancieroPorDefectoResolver::class)->resolver();
    }
}
