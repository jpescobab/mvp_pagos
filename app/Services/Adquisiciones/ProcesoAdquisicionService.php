<?php

namespace App\Services\Adquisiciones;

use App\Exceptions\ProcesoAdquisicionException;
use App\Models\DefinicionWorkflow;
use App\Models\ModalidadAdquisicion;
use App\Models\Proceso;
use App\Models\ProcesoAdquisicion;
use App\Services\Indicadores\IndicadorEconomicoSelector;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProcesoAdquisicionService
{
    public function __construct(private readonly IndicadorEconomicoSelector $indicadorSelector) {}

    /**
     * @param  array<string, mixed>  $datos  Antecedentes generales de la solicitud, incluyendo convenio_marco
     *                                       (bool, deriva la modalidad) en vez de modalidad_id.
     */
    public function crear(array $datos): ProcesoAdquisicion
    {
        $modalidad = $this->resolverModalidadDesdeConvenioMarco((bool) $datos['convenio_marco']);
        $fechaInicio = Carbon::parse($datos['fecha_inicio']);
        $calculado = $this->resolverMonedaYMonto($datos);

        $this->validarMontoBajoUmbralUtm($calculado['monto_estimado'], $fechaInicio);

        $datos = Arr::except($datos, ['convenio_marco', 'moneda_compra', 'monto_estimado_solicitado', 'fecha_paridad']);
        $datos = [...$datos, ...$calculado];
        $datos['modalidad_id'] = $modalidad->id;
        $datos['objeto'] = $datos['caracteristicas'];

        return DB::transaction(function () use ($datos, $fechaInicio) {
            // Correlativo basado en el id autoincremental, mismo patrón que
            // CrearBorradorCertificadoDisponibilidadService para el folio del
            // CDP: un SELECT MAX()+1 no serializa de forma segura contra
            // inserciones concurrentes bajo READ COMMITTED en PostgreSQL. Se
            // inserta con un código temporal único y se fija el definitivo
            // una vez conocido el id, dentro de la misma transacción.
            $proceso = ProcesoAdquisicion::create([
                ...$datos,
                'codigo' => 'TEMP-'.Str::uuid(),
            ]);

            $proceso->update(['codigo' => sprintf('SPC-%03d-%d', $proceso->id, $fechaInicio->year)]);

            $definicion = DefinicionWorkflow::where('codigo', 'adquisiciones')->firstOrFail();
            $estadoInicial = $definicion->estados()->where('es_inicial', true)->firstOrFail();

            Proceso::create([
                'definicion_workflow_id' => $definicion->id,
                'estado_actual_id' => $estadoInicial->id,
                'sujeto_type' => ProcesoAdquisicion::class,
                'sujeto_id' => $proceso->id,
                'modalidad_id' => $proceso->modalidad_id,
                'monto' => $proceso->monto_estimado,
            ]);

            return $proceso->refresh();
        });
    }

    /**
     * Actualiza un proceso de adquisición que está en borrador y sincroniza los
     * campos que gobiernan el checklist (modalidad_id, monto) en su Proceso.
     *
     * @param  array<string, mixed>  $datos  Antecedentes generales de la solicitud, incluyendo convenio_marco
     *                                       (bool, deriva la modalidad) en vez de modalidad_id.
     */
    public function actualizar(ProcesoAdquisicion $proceso, array $datos): ProcesoAdquisicion
    {
        $estadoActual = $proceso->proceso->estadoActual->codigo;

        if ($estadoActual !== 'borrador') {
            throw ProcesoAdquisicionException::noEditableEnEstado($estadoActual);
        }

        $modalidad = $this->resolverModalidadDesdeConvenioMarco((bool) $datos['convenio_marco']);
        $fechaInicio = Carbon::parse($datos['fecha_inicio']);
        $calculado = $this->resolverMonedaYMonto($datos);

        $this->validarMontoBajoUmbralUtm($calculado['monto_estimado'], $fechaInicio);

        $datos = Arr::except($datos, ['convenio_marco', 'moneda_compra', 'monto_estimado_solicitado', 'fecha_paridad']);
        $datos = [...$datos, ...$calculado];
        $datos['modalidad_id'] = $modalidad->id;
        $datos['objeto'] = $datos['caracteristicas'];

        return DB::transaction(function () use ($proceso, $datos) {
            $proceso->update($datos);

            $proceso->proceso->update([
                'modalidad_id' => $proceso->modalidad_id,
                'monto' => $proceso->monto_estimado,
            ]);

            return $proceso->refresh();
        });
    }

    private function resolverModalidadDesdeConvenioMarco(bool $convenioMarco): ModalidadAdquisicion
    {
        $modalidad = ModalidadAdquisicion::where('codigo', $convenioMarco ? 'CONVENIO_MARCO' : 'TRATO_DIRECTO')
            ->where('activo', true)
            ->first();

        if ($modalidad === null) {
            throw ProcesoAdquisicionException::modalidadInvalida();
        }

        return $modalidad;
    }

    /**
     * Resuelve `paridad` y `monto_estimado` a partir de `moneda_compra`/
     * `fecha_paridad`/`monto_estimado_solicitado` — mismo patrón que
     * `CrearBorradorCertificadoDisponibilidadService::resolverParidadYMonto()`
     * para el CDP. En CLP no hay paridad (monto_estimado = solicitado). En
     * UF/USD se resuelve contra el indicador económico real vigente para
     * `fecha_paridad` (`IndicadorEconomicoSelector`), nunca un valor
     * ingresado a mano.
     *
     * @param  array<string, mixed>  $datos
     * @return array{moneda_compra: string, monto_estimado_solicitado: float, fecha_paridad: ?string, paridad: ?float, monto_estimado: float}
     */
    private function resolverMonedaYMonto(array $datos): array
    {
        $moneda = $datos['moneda_compra'] ?? 'CLP';
        $solicitado = (float) $datos['monto_estimado_solicitado'];

        if ($moneda === 'CLP') {
            return [
                'moneda_compra' => 'CLP',
                'monto_estimado_solicitado' => $solicitado,
                'fecha_paridad' => null,
                'paridad' => null,
                'monto_estimado' => round($solicitado, 2),
            ];
        }

        $fechaParidad = Carbon::parse($datos['fecha_paridad']);
        $indicador = $this->indicadorSelector->paraFecha($moneda, $fechaParidad);

        if ($indicador === null) {
            throw ProcesoAdquisicionException::sinIndicadorParaFecha($moneda, $fechaParidad->toDateString());
        }

        $paridad = (float) $indicador->valor;

        return [
            'moneda_compra' => $moneda,
            'monto_estimado_solicitado' => $solicitado,
            'fecha_paridad' => $fechaParidad->toDateString(),
            'paridad' => $paridad,
            'monto_estimado' => round($solicitado * $paridad, 2),
        ];
    }

    /**
     * Este flujo de creación es específico para compras menores a 1.000 UTM
     * (licitación pública para montos mayores queda fuera de este formulario).
     * Si no hay un valor UTM vigente para el período de la fecha de inicio, no
     * se bloquea la creación: es un vacío de datos de indicadores económicos,
     * no un error del usuario.
     */
    private function validarMontoBajoUmbralUtm(float $montoEstimado, CarbonInterface $fecha): void
    {
        $indicador = $this->indicadorSelector->paraPeriodo('UTM', $fecha->format('Y-m'));

        if ($indicador === null) {
            return;
        }

        if ($montoEstimado >= 1000 * (float) $indicador->valor) {
            throw ProcesoAdquisicionException::montoSobreUmbralUtm();
        }
    }
}
