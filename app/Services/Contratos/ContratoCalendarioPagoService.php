<?php

namespace App\Services\Contratos;

use App\Exceptions\ContratoException;
use App\Models\CasoPagoProveedor;
use App\Models\Contrato;
use App\Models\ContratoCuota;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ContratoCalendarioPagoService
{
    /**
     * Meses que suma cada periodicidad para espaciar las fechas de
     * vencimiento entre la vigencia del contrato.
     *
     * @var array<string, int>
     */
    private const MESES_POR_PERIODICIDAD = [
        'mensual' => 1,
        'bimestral' => 2,
        'trimestral' => 3,
        'semestral' => 6,
        'anual' => 12,
    ];

    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly ContratoService $contratoService,
    ) {}

    /**
     * Genera las ContratoCuota del calendario de pago a partir de la
     * vigencia y periodicidad del contrato, distribuyendo `monto_total`
     * entre las cuotas (la última ajusta el resto de la división).
     *
     * @return Collection<int, ContratoCuota>
     */
    public function generarCalendario(Contrato $contrato): Collection
    {
        $this->contratoService->exigirBorrador($contrato);

        if (! $contrato->tiene_calendario_pago) {
            throw ContratoException::calendarioPagoNoHabilitado();
        }

        if ($contrato->monto_total === null) {
            throw ContratoException::montoTotalRequeridoParaCalendario();
        }

        $fechas = $this->calcularFechasVencimiento($contrato);
        $montos = $this->distribuirMonto((float) $contrato->monto_total, count($fechas));

        return DB::transaction(function () use ($contrato, $fechas, $montos) {
            $contrato->cuotas()->delete();

            $cuotas = collect($fechas)->values()->map(fn (Carbon $fecha, int $indice) => $contrato->cuotas()->create([
                'numero_cuota' => $indice + 1,
                'fecha_vencimiento' => $fecha->toDateString(),
                'monto' => $montos[$indice],
                'moneda' => 'CLP',
                'estado' => ContratoCuota::ESTADO_PENDIENTE,
            ]));

            $this->auditLogger->log(
                action: 'contrato.generar_calendario_pago',
                auditable: $contrato,
                after: ['cantidad_cuotas' => $cuotas->count(), 'periodicidad_pago' => $contrato->periodicidad_pago],
            );

            return $cuotas;
        });
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public function actualizarCuota(ContratoCuota $cuota, array $datos): ContratoCuota
    {
        $this->contratoService->exigirBorrador($cuota->contrato);

        $cuota->update($datos);

        return $cuota->refresh();
    }

    public function vincularPago(ContratoCuota $cuota, CasoPagoProveedor $casoPagoProveedor): ContratoCuota
    {
        if ($cuota->caso_pago_proveedor_id !== null) {
            throw ContratoException::cuotaYaVinculada();
        }

        return DB::transaction(function () use ($cuota, $casoPagoProveedor) {
            $cuota->update([
                'caso_pago_proveedor_id' => $casoPagoProveedor->id,
                'estado' => ContratoCuota::ESTADO_PAGADA,
            ]);

            $this->auditLogger->log(
                action: 'contrato_cuota.vincular_pago',
                auditable: $cuota,
                before: ['caso_pago_proveedor_id' => null, 'estado' => ContratoCuota::ESTADO_PENDIENTE],
                after: ['caso_pago_proveedor_id' => $casoPagoProveedor->id, 'estado' => ContratoCuota::ESTADO_PAGADA],
            );

            return $cuota->refresh();
        });
    }

    public function desvincularPago(ContratoCuota $cuota): ContratoCuota
    {
        return DB::transaction(function () use ($cuota) {
            $anterior = $cuota->caso_pago_proveedor_id;

            $cuota->update([
                'caso_pago_proveedor_id' => null,
                'estado' => ContratoCuota::ESTADO_PENDIENTE,
            ]);

            $this->auditLogger->log(
                action: 'contrato_cuota.desvincular_pago',
                auditable: $cuota,
                before: ['caso_pago_proveedor_id' => $anterior, 'estado' => ContratoCuota::ESTADO_PAGADA],
                after: ['caso_pago_proveedor_id' => null, 'estado' => ContratoCuota::ESTADO_PENDIENTE],
            );

            return $cuota->refresh();
        });
    }

    /**
     * @return list<Carbon>
     */
    private function calcularFechasVencimiento(Contrato $contrato): array
    {
        $inicio = Carbon::parse($contrato->fecha_inicio_vigencia);
        $fin = Carbon::parse($contrato->fecha_fin_vigencia);

        if ($contrato->periodicidad_pago === 'unica') {
            return [$fin->copy()];
        }

        $meses = self::MESES_POR_PERIODICIDAD[$contrato->periodicidad_pago] ?? 1;

        $fechas = [];
        $cursor = $inicio->copy()->addMonthsNoOverflow($meses);

        while ($cursor->lessThanOrEqualTo($fin)) {
            $fechas[] = $cursor->copy();
            $cursor = $cursor->addMonthsNoOverflow($meses);
        }

        if ($fechas === []) {
            $fechas[] = $fin->copy();
        } elseif ($fechas[count($fechas) - 1]->notEqualTo($fin)) {
            $fechas[count($fechas) - 1] = $fin->copy();
        }

        return $fechas;
    }

    /**
     * Distribuye el monto total entre `n` cuotas iguales, ajustando la
     * última por cualquier resto de la división para que la suma cuadre
     * exactamente con `monto_total`.
     *
     * @return list<float>
     */
    private function distribuirMonto(float $montoTotal, int $cantidadCuotas): array
    {
        $montoBase = round($montoTotal / $cantidadCuotas, 2);
        $montos = array_fill(0, $cantidadCuotas, $montoBase);

        $resto = round($montoTotal - ($montoBase * $cantidadCuotas), 2);
        $montos[$cantidadCuotas - 1] = round($montos[$cantidadCuotas - 1] + $resto, 2);

        return array_values($montos);
    }
}
