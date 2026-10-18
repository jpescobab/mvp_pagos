<?php

namespace App\Services\ServiciosContratados;

use App\Exceptions\ServicioContratadoException;
use App\Models\Contrato;
use App\Models\ServicioContratado;
use Illuminate\Support\Carbon;

class ServicioContratadoService
{
    /**
     * @param  array<string, mixed>  $datos
     */
    public function registrar(array $datos): ServicioContratado
    {
        $contrato = Contrato::with('proceso.estadoActual')->findOrFail((int) $datos['contrato_id']);

        if ($contrato->proceso?->estadoActual->codigo !== 'aprobado') {
            throw ServicioContratadoException::contratoNoAprobado();
        }

        $this->validarVigencia($contrato, $datos['fecha_inicio'], $datos['fecha_fin']);

        return ServicioContratado::create($datos);
    }

    /**
     * El contrato de un servicio no cambia: se ignora `contrato_id` si llega.
     *
     * @param  array<string, mixed>  $datos
     */
    public function actualizar(ServicioContratado $servicio, array $datos): ServicioContratado
    {
        $this->exigirNoTerminado($servicio);
        unset($datos['contrato_id']);

        $this->validarVigencia(
            $servicio->contrato,
            $datos['fecha_inicio'] ?? $servicio->fecha_inicio,
            $datos['fecha_fin'] ?? $servicio->fecha_fin,
        );

        $servicio->update($datos);

        return $servicio->refresh();
    }

    public function terminar(ServicioContratado $servicio): ServicioContratado
    {
        $this->exigirNoTerminado($servicio);

        $servicio->update(['terminado_en' => now()]);

        return $servicio->refresh();
    }

    private function exigirNoTerminado(ServicioContratado $servicio): void
    {
        if ($servicio->terminado_en !== null) {
            throw ServicioContratadoException::servicioTerminado();
        }
    }

    private function validarVigencia(Contrato $contrato, mixed $inicio, mixed $fin): void
    {
        $inicio = Carbon::parse($inicio)->startOfDay();
        $fin = Carbon::parse($fin)->startOfDay();

        if ($fin->lt($inicio)
            || $inicio->lt($contrato->fecha_inicio_vigencia->copy()->startOfDay())
            || $fin->gt($contrato->fecha_fin_vigencia->copy()->startOfDay())) {
            throw ServicioContratadoException::vigenciaInvalida();
        }
    }
}
