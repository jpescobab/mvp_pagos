<?php

namespace App\Services\Presupuesto;

use App\Models\Presupuesto\MovimientoPresupuestario;
use App\Models\Presupuesto\Presupuesto;

class CalculadorSaldoPresupuestoService
{
    /**
     * Saldo disponible = monto_asignado − (compromisos − liberaciones) − ejecutado.
     *
     * Bloquea las filas de movimiento de la línea (lockForUpdate) para que dos
     * firmas concurrentes contra la misma línea no lean el mismo saldo antes de
     * comprometer — debe llamarse dentro de una transacción abierta por el
     * llamador.
     */
    public function disponible(Presupuesto $presupuesto): float
    {
        $movimientos = MovimientoPresupuestario::where('presupuesto_id', $presupuesto->id)
            ->lockForUpdate()
            ->get();

        $compromiso = (float) $movimientos->where('tipo', 'compromiso')->sum('monto');
        $liberacion = (float) $movimientos->where('tipo', 'liberacion_compromiso')->sum('monto');
        $ejecucion = (float) $movimientos->where('tipo', 'ejecucion')->sum('monto');

        return (float) $presupuesto->monto_asignado - ($compromiso - $liberacion) - $ejecucion;
    }
}
