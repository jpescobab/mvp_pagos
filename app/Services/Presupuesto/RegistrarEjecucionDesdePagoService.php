<?php

namespace App\Services\Presupuesto;

use App\Models\CasoPagoProveedor;
use App\Models\DefinicionWorkflow;
use App\Models\Presupuesto\CertificadoDisponibilidadPresupuestaria;
use App\Models\Presupuesto\MovimientoPresupuestario;
use App\Models\Presupuesto\Presupuesto;
use App\Models\User;
use App\Notifications\SobreEjecucionCdpNotification;
use App\Services\AuditLogger;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Refleja un pago concretado como ejecución presupuestaria: crea un
 * movimiento `ejecucion` y otro `liberacion_compromiso` por el monto del caso
 * contra el CDP firmado vigente de su adquisición. Este sistema no gobierna el
 * presupuesto (CGU sí): nunca bloquea el pago, solo lo refleja y alerta.
 */
class RegistrarEjecucionDesdePagoService
{
    public const REGISTRADA = 'registrada';

    public const YA_REGISTRADA = 'ya_registrada';

    public const MODULO_INACTIVO = 'modulo_inactivo';

    public const SIN_ADQUISICION = 'sin_adquisicion';

    public const SIN_MONTO = 'sin_monto';

    public const SIN_CDP_VIGENTE = 'sin_cdp_vigente';

    public const VARIOS_CDP_VIGENTES = 'varios_cdp_vigentes';

    public function __construct(private readonly AuditLogger $auditoria) {}

    public function registrar(CasoPagoProveedor $caso, ?User $user = null): string
    {
        if (! $this->moduloActivo()) {
            return self::MODULO_INACTIVO;
        }

        if ($caso->proceso_adquisicion_id === null) {
            return self::SIN_ADQUISICION;
        }

        if ((float) $caso->monto <= 0) {
            $this->dejarConstancia($caso, $user, 'El caso no tiene un monto positivo para ejecutar.', []);

            return self::SIN_MONTO;
        }

        $vigentes = $this->cdpsVigentes($caso->proceso_adquisicion_id);

        if ($vigentes->count() !== 1) {
            $motivo = $vigentes->isEmpty()
                ? 'La adquisición no tiene ningún CDP firmado vigente.'
                : 'La adquisición tiene más de un CDP firmado vigente; se requiere resolución humana.';

            $this->dejarConstancia($caso, $user, $motivo, $vigentes->pluck('folio', 'id')->all());

            return $vigentes->isEmpty() ? self::SIN_CDP_VIGENTE : self::VARIOS_CDP_VIGENTES;
        }

        /** @var CertificadoDisponibilidadPresupuestaria $cdp */
        $cdp = $vigentes->first();

        try {
            $montoEjecutado = DB::transaction(function () use ($caso, $cdp, $user): ?float {
                Presupuesto::where('id', $cdp->presupuesto_id)->lockForUpdate()->firstOrFail();

                $yaRegistrada = MovimientoPresupuestario::where('origen_type', $caso->getMorphClass())
                    ->where('origen_id', $caso->id)
                    ->whereIn('tipo', ['ejecucion', 'liberacion_compromiso'])
                    ->exists();

                if ($yaRegistrada) {
                    return null;
                }

                foreach (['ejecucion', 'liberacion_compromiso'] as $tipo) {
                    MovimientoPresupuestario::create([
                        'presupuesto_id' => $cdp->presupuesto_id,
                        'certificado_disponibilidad_presupuestaria_id' => $cdp->id,
                        'tipo' => $tipo,
                        'monto' => $caso->monto,
                        'origen_type' => $caso->getMorphClass(),
                        'origen_id' => $caso->id,
                        'user_id' => $user?->id,
                        'observacion' => "Pago del caso {$caso->sgf_id} contra {$cdp->folio}",
                    ]);
                }

                return (float) $cdp->movimientosEjecucion()->sum('monto');
            });
        } catch (UniqueConstraintViolationException) {
            return self::YA_REGISTRADA;
        }

        if ($montoEjecutado === null) {
            return self::YA_REGISTRADA;
        }

        if ($montoEjecutado > (float) $cdp->monto) {
            $this->alertarSobreEjecucion($cdp, $caso, $montoEjecutado, $user);
        }

        return self::REGISTRADA;
    }

    /**
     * CDP firmado, de monto positivo y sin un CDP de anulación firmado que lo
     * referencie.
     *
     * @return Collection<int, CertificadoDisponibilidadPresupuestaria>
     */
    private function cdpsVigentes(int $procesoAdquisicionId): Collection
    {
        return CertificadoDisponibilidadPresupuestaria::query()
            ->where('proceso_adquisicion_id', $procesoAdquisicionId)
            ->where('monto', '>', 0)
            ->whereHas('proceso.estadoActual', fn ($query) => $query->where('codigo', 'firmado'))
            ->whereDoesntHave('anulaciones', fn ($query) => $query->whereHas(
                'proceso.estadoActual',
                fn ($estado) => $estado->where('codigo', 'firmado'),
            ))
            ->orderBy('id')
            ->get();
    }

    private function moduloActivo(): bool
    {
        return DefinicionWorkflow::where('codigo', 'presupuesto_cdp')->value('activo') === true;
    }

    /**
     * @param  array<int, string>  $cdpCandidatos  id => folio
     */
    private function dejarConstancia(CasoPagoProveedor $caso, ?User $user, string $motivo, array $cdpCandidatos): void
    {
        $this->auditoria->log(
            action: 'presupuesto.ejecucion_no_resuelta',
            auditable: $caso,
            after: ['motivo' => $motivo, 'cdp_candidatos' => $cdpCandidatos],
            user: $user,
        );
    }

    private function alertarSobreEjecucion(CertificadoDisponibilidadPresupuestaria $cdp, CasoPagoProveedor $caso, float $montoEjecutado, ?User $user): void
    {
        $this->auditoria->log(
            action: 'presupuesto.sobre_ejecucion',
            auditable: $cdp,
            after: ['monto_cdp' => (float) $cdp->monto, 'monto_ejecutado' => $montoEjecutado],
            metadata: ['caso_pago_proveedor_id' => $caso->id],
            user: $user,
        );

        Notification::send(
            User::permission('presupuesto.firmar_cdp')->get(),
            new SobreEjecucionCdpNotification($cdp, $caso, $montoEjecutado),
        );
    }
}
