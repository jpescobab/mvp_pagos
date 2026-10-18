<?php

namespace App\Models;

use App\Models\Concerns\RegistraAuditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property-read Carbon $fecha_inicio
 * @property-read Carbon $fecha_fin
 * @property-read Contrato $contrato
 */
class ServicioContratado extends Model
{
    use RegistraAuditoria;

    public const CATEGORIAS = ['aseo', 'vigilancia', 'mantencion', 'arriendo', 'otro'];

    public const ESTADO_VIGENTE = 'vigente';

    public const ESTADO_POR_VENCER = 'por_vencer';

    public const ESTADO_VENCIDO = 'vencido';

    public const ESTADO_TERMINADO = 'terminado';

    protected $table = 'servicios_contratados';

    protected $fillable = [
        'contrato_id',
        'nombre',
        'categoria',
        'monto_mensual_esperado',
        'moneda',
        'fecha_inicio',
        'fecha_fin',
        'ccosto_id',
        'administrador_funcionario_id',
        'observaciones',
        'terminado_en',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date:Y-m-d',
            'fecha_fin' => 'date:Y-m-d',
            'monto_mensual_esperado' => 'decimal:2',
            'terminado_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Contrato, $this>
     */
    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    /**
     * @return BelongsTo<Ccosto, $this>
     */
    public function ccosto(): BelongsTo
    {
        return $this->belongsTo(Ccosto::class);
    }

    /**
     * @return BelongsTo<Funcionario, $this>
     */
    public function administrador(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class, 'administrador_funcionario_id');
    }

    /**
     * Estado derivado de la vigencia al momento de la consulta — nunca
     * persistido (ver design.md del change modulo-servicios-contratados).
     */
    public function estadoDerivado(?Carbon $hoy = null): string
    {
        $hoy = ($hoy ?? now())->copy()->startOfDay();

        if ($this->terminado_en !== null) {
            return self::ESTADO_TERMINADO;
        }

        if ($this->fecha_fin->copy()->startOfDay()->lt($hoy)) {
            return self::ESTADO_VENCIDO;
        }

        $limite = $hoy->copy()->addDays((int) config('servicios_contratados.dias_alerta_vencimiento'));

        return $this->fecha_fin->copy()->startOfDay()->lte($limite)
            ? self::ESTADO_POR_VENCER
            : self::ESTADO_VIGENTE;
    }

    /**
     * Filtra por estado derivado con la misma regla que estadoDerivado().
     *
     * @param  Builder<ServicioContratado>  $query
     */
    public function scopeConEstado(Builder $query, string $estado, ?Carbon $hoy = null): void
    {
        $hoy = ($hoy ?? now())->copy()->startOfDay();
        $limite = $hoy->copy()->addDays((int) config('servicios_contratados.dias_alerta_vencimiento'));

        match ($estado) {
            self::ESTADO_TERMINADO => $query->whereNotNull('terminado_en'),
            self::ESTADO_VENCIDO => $query->whereNull('terminado_en')->whereDate('fecha_fin', '<', $hoy),
            self::ESTADO_POR_VENCER => $query->whereNull('terminado_en')
                ->whereDate('fecha_fin', '>=', $hoy)->whereDate('fecha_fin', '<=', $limite),
            self::ESTADO_VIGENTE => $query->whereNull('terminado_en')->whereDate('fecha_fin', '>', $limite),
            default => null,
        };
    }
}
