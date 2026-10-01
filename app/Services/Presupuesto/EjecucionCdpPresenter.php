<?php

namespace App\Services\Presupuesto;

use App\Models\CasoPagoProveedor;
use App\Models\Presupuesto\CertificadoDisponibilidadPresupuestaria;

/**
 * Presenta la ejecución presupuestaria de un CDP (pagos concretados contra
 * él) para el detalle. Requiere cargadas las relaciones
 * `movimientosEjecucion.origen` y `proceso.estadoActual`.
 */
class EjecucionCdpPresenter
{
    /**
     * @return array{pagos: list<array{caso_id: int, sgf_id: ?string, monto: float, registrado_en: ?string}>, monto_ejecutado: float, compromiso_remanente: float, sobre_ejecutado: bool}|null
     */
    public function presentar(CertificadoDisponibilidadPresupuestaria $cdp): ?array
    {
        if ($cdp->proceso?->estadoActual?->codigo !== 'firmado' || (float) $cdp->monto <= 0) {
            return null;
        }

        $pagos = [];
        $montoEjecutado = 0.0;

        foreach ($cdp->movimientosEjecucion as $movimiento) {
            $caso = $movimiento->origen instanceof CasoPagoProveedor ? $movimiento->origen : null;
            $monto = (float) $movimiento->monto;
            $montoEjecutado += $monto;

            $pagos[] = [
                'caso_id' => (int) $movimiento->origen_id,
                'sgf_id' => $caso?->sgf_id,
                'monto' => $monto,
                'registrado_en' => $movimiento->created_at?->toIso8601String(),
            ];
        }

        $montoCdp = (float) $cdp->monto;

        return [
            'pagos' => $pagos,
            'monto_ejecutado' => $montoEjecutado,
            'compromiso_remanente' => max(0.0, $montoCdp - $montoEjecutado),
            'sobre_ejecutado' => $montoEjecutado > $montoCdp,
        ];
    }
}
