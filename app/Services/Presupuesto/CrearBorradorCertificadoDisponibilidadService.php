<?php

namespace App\Services\Presupuesto;

use App\Exceptions\CertificadoDisponibilidadPresupuestariaException;
use App\Models\DefinicionWorkflow;
use App\Models\Presupuesto\CertificadoDisponibilidadPresupuestaria;
use App\Models\Presupuesto\Presupuesto;
use App\Models\Proceso;
use App\Services\Indicadores\IndicadorEconomicoSelector;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CrearBorradorCertificadoDisponibilidadService
{
    public function __construct(
        private readonly IndicadorEconomicoSelector $indicadorEconomicoSelector,
    ) {}

    /**
     * @param  array<string, mixed>  $datos  presupuesto_id, tipo_gasto, codigo_iniciativa?,
     *                                       nombre, nombre_iniciativa?, programa_presupuestario?,
     *                                       caracter_gasto, medio_solicitud?, fecha_solicitud?,
     *                                       moneda_compra?, total_moneda_compra, fecha_paridad?,
     *                                       anio_validez, requerimiento_numero?,
     *                                       mercado_publico_tipo?, mercado_publico_id?,
     *                                       proceso_adquisicion_id?, cdp_original_id? —
     *                                       `paridad`/`monto` NO se aceptan como input: los
     *                                       calcula `resolverParidadYMonto()`.
     */
    public function crear(array $datos): CertificadoDisponibilidadPresupuestaria
    {
        $presupuesto = Presupuesto::with('catalogo.item', 'cfinanciero')
            ->find((int) $datos['presupuesto_id']);

        if (! $presupuesto instanceof Presupuesto) {
            throw CertificadoDisponibilidadPresupuestariaException::lineaPresupuestoInexistente();
        }

        $cfinanciero = $presupuesto->cfinanciero;
        $catalogo = $presupuesto->catalogo;

        return DB::transaction(function () use ($datos, $cfinanciero, $catalogo) {
            $anio = (int) $datos['anio_validez'];
            $calculado = $this->resolverParidadYMonto($datos);

            // El correlativo del folio es el `id` autoincremental de la propia
            // tabla — único y atómico de forma nativa por el motor de base de
            // datos, sin necesidad de bloqueos manuales. Un SELECT MAX()+1
            // (con o sin lockForUpdate) no serializa de forma segura contra
            // inserciones concurrentes bajo READ COMMITTED en PostgreSQL, lo
            // que producía folios duplicados en pruebas reales. Se inserta con
            // un folio temporal único y se fija el definitivo una vez conocido
            // el id, dentro de la misma transacción.
            $cdp = CertificadoDisponibilidadPresupuestaria::create([
                ...$datos,
                ...$calculado,
                'folio' => 'TEMP-'.Str::uuid(),
                'cfinanciero_id' => $cfinanciero->id,
                'denominacion' => $catalogo->nombre,
                'unidad_ejecutora' => $cfinanciero->nombre,
                'n_ue' => $cfinanciero->codigo,
                'subtitulo' => substr($catalogo->item->codigo, 0, 2),
                'programa_presupuestario' => $datos['programa_presupuestario'] ?? '100',
            ]);

            $cdp->update(['folio' => sprintf('CDP %03d-%d', $cdp->id, $anio)]);

            $definicion = DefinicionWorkflow::where('codigo', 'presupuesto_cdp')->firstOrFail();
            $estadoInicial = $definicion->estados()->where('es_inicial', true)->firstOrFail();

            Proceso::create([
                'definicion_workflow_id' => $definicion->id,
                'estado_actual_id' => $estadoInicial->id,
                'sujeto_type' => CertificadoDisponibilidadPresupuestaria::class,
                'sujeto_id' => $cdp->id,
                'monto' => $cdp->monto,
                'iniciado_por' => Auth::id(),
            ]);

            return $cdp->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public function actualizar(CertificadoDisponibilidadPresupuestaria $cdp, array $datos): CertificadoDisponibilidadPresupuestaria
    {
        $estadoActual = $cdp->proceso->estadoActual->codigo;

        if ($estadoActual !== 'borrador') {
            throw CertificadoDisponibilidadPresupuestariaException::noEditableEnEstado($estadoActual);
        }

        return DB::transaction(function () use ($cdp, $datos) {
            $calculado = $this->resolverParidadYMonto($datos);

            $cdp->update([...$datos, ...$calculado]);

            $cdp->proceso->update(['monto' => $cdp->monto]);

            return $cdp->refresh();
        });
    }

    /**
     * Resuelve `paridad` y `monto` a partir de `moneda_compra`/`fecha_paridad`/
     * `total_moneda_compra` — nunca se aceptan como entrada directa del
     * cliente. En CLP no hay paridad (monto = total). En UF/USD se resuelve
     * contra el indicador económico real vigente para `fecha_paridad`
     * (`IndicadorEconomicoSelector`), la misma fuente que ya usa el resto del
     * sistema — no un valor ingresado a mano.
     *
     * @param  array<string, mixed>  $datos
     * @return array{moneda_compra: string, fecha_paridad: ?string, paridad: ?float, monto: float}
     */
    private function resolverParidadYMonto(array $datos): array
    {
        $moneda = $datos['moneda_compra'] ?? 'CLP';
        $total = (float) $datos['total_moneda_compra'];

        if ($moneda === 'CLP') {
            return [
                'moneda_compra' => 'CLP',
                'fecha_paridad' => null,
                'paridad' => null,
                'monto' => round($total, 2),
            ];
        }

        $fechaParidad = Carbon::parse($datos['fecha_paridad']);
        $indicador = $this->indicadorEconomicoSelector->paraFecha($moneda, $fechaParidad);

        if ($indicador === null) {
            throw CertificadoDisponibilidadPresupuestariaException::sinIndicadorParaFecha($moneda, $fechaParidad->toDateString());
        }

        $paridad = (float) $indicador->valor;

        return [
            'moneda_compra' => $moneda,
            'fecha_paridad' => $fechaParidad->toDateString(),
            'paridad' => $paridad,
            'monto' => round($total * $paridad, 2),
        ];
    }
}
