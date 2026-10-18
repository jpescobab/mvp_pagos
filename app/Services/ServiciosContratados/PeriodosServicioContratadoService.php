<?php

namespace App\Services\ServiciosContratados;

use App\Models\CasoPagoProveedor;
use App\Models\ServicioContratado;
use Illuminate\Support\Carbon;

/**
 * Períodos mensuales de un servicio, derivados al consultar y cruzados con
 * los casos de pago del proveedor. Es informativo: nunca altera ni bloquea un
 * caso (SGF es origen, CGU gobierna el presupuesto).
 */
class PeriodosServicioContratadoService
{
    public const PAGADO = 'pagado';

    public const EN_TRAMITE = 'en_tramite';

    public const SIN_CASO = 'sin_caso';

    public const AMBIGUO = 'ambiguo';

    /** Estados del workflow de pago desde los que el caso se considera pagado. */
    private const ESTADOS_PAGADO = ['pagada_bancoestado', 'asociada_a_egreso_cgu', 'cerrada'];

    /**
     * @return list<array{periodo: string, estado: string, casos: list<array{id: int, sgf_id: string, estado: string|null}>}>
     */
    public function periodos(ServicioContratado $servicio, ?Carbon $hoy = null): array
    {
        $hoy ??= now();
        $proveedorId = $servicio->contrato->proveedor_id;

        $primero = $servicio->fecha_inicio->copy()->startOfMonth();
        $ultimo = $servicio->fecha_fin->copy()->startOfMonth();

        if ($ultimo->gt($hoy->copy()->startOfMonth())) {
            $ultimo = $hoy->copy()->startOfMonth();
        }

        if ($ultimo->lt($primero)) {
            return [];
        }

        $casosPorPeriodo = $this->casosPorPeriodo($proveedorId);
        $otros = ServicioContratado::query()
            ->whereKeyNot($servicio->id)
            ->whereHas('contrato', fn ($q) => $q->where('proveedor_id', $proveedorId))
            ->get(['id', 'fecha_inicio', 'fecha_fin']);

        $periodos = [];

        for ($mes = $primero->copy(); $mes->lte($ultimo); $mes = $mes->copy()->addMonth()) {
            $clave = $mes->format('Y-m');
            $casos = $casosPorPeriodo[$clave] ?? [];
            $inicioMes = $mes->copy()->startOfMonth();
            $finMes = $mes->copy()->endOfMonth();

            $concurrente = $otros->contains(
                fn (ServicioContratado $otro) => $otro->fecha_inicio->lte($finMes) && $otro->fecha_fin->gte($inicioMes),
            );

            $periodos[] = [
                'periodo' => $clave,
                'estado' => $this->estadoPeriodo($casos, $concurrente),
                'casos' => array_map(fn (array $c) => [
                    'id' => $c['id'],
                    'sgf_id' => $c['sgf_id'],
                    'estado' => $c['estado'],
                ], $casos),
            ];
        }

        return $periodos;
    }

    /**
     * @param  list<array{id: int, sgf_id: string, estado: string|null}>  $casos
     */
    private function estadoPeriodo(array $casos, bool $concurrente): string
    {
        if ($casos === []) {
            return self::SIN_CASO;
        }

        if ($concurrente) {
            return self::AMBIGUO;
        }

        foreach ($casos as $caso) {
            if (in_array($caso['estado'], self::ESTADOS_PAGADO, true)) {
                return self::PAGADO;
            }
        }

        return self::EN_TRAMITE;
    }

    /**
     * @return array<string, list<array{id: int, sgf_id: string, estado: string|null}>>
     */
    private function casosPorPeriodo(int $proveedorId): array
    {
        $agrupados = [];

        CasoPagoProveedor::query()
            ->where('proveedor_id', $proveedorId)
            ->whereNotNull('periodo')
            ->with('proceso.estadoActual')
            ->get()
            ->each(function (CasoPagoProveedor $caso) use (&$agrupados): void {
                if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $caso->periodo) !== 1) {
                    return;
                }

                $agrupados[$caso->periodo][] = [
                    'id' => $caso->id,
                    'sgf_id' => $caso->sgf_id,
                    'estado' => $caso->proceso?->estadoActual->codigo,
                ];
            });

        return $agrupados;
    }
}
