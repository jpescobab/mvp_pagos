<?php

namespace App\Services\Contratos;

use App\Exceptions\ContratoException;
use App\Exceptions\ContratoSinProveedorException;
use App\Models\Contrato;
use App\Models\ContratoItemConvenioPrecio;
use App\Models\DefinicionWorkflow;
use App\Models\Proceso;
use App\Models\Proveedor;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContratoService
{
    /**
     * Columnas del catálogo de proveedores que se completan (nunca
     * sobrescriben un valor ya cargado) cuando el proveedor del contrato ya
     * existe — mismo criterio que
     * OrdenCompraMercadoPublicoService::CAMPOS_COMPLETABLES_PROVEEDOR.
     *
     * @var list<string>
     */
    private const CAMPOS_COMPLETABLES_PROVEEDOR = [
        'nombre',
        'direccion',
        'comuna',
        'region',
        'giro',
        'correo',
        'contacto',
        'contacto_cargo',
        'contacto_telefono',
    ];

    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $datos
     * @param  array<string, mixed>|null  $datosProveedor  Datos del proveedor a resolver/crear por RUT, cuando no se indica proveedor_id.
     */
    public function crear(array $datos, ?array $datosProveedor = null): Contrato
    {
        return DB::transaction(function () use ($datos, $datosProveedor) {
            $datos['proveedor_id'] = $this->resolverProveedor($datos['proveedor_id'] ?? null, $datosProveedor)->id;

            // Mismo patrón que ProcesoAdquisicionService/CDP: código temporal
            // único, luego se fija el definitivo con el id ya conocido,
            // dentro de la misma transacción.
            $contrato = Contrato::create([
                ...$datos,
                'codigo' => 'TEMP-'.Str::uuid(),
            ]);

            $contrato->update(['codigo' => sprintf('CTR-%03d-%d', $contrato->id, now()->year)]);

            $definicion = DefinicionWorkflow::where('codigo', 'contratos')->firstOrFail();
            $estadoInicial = $definicion->estados()->where('es_inicial', true)->firstOrFail();

            Proceso::create([
                'definicion_workflow_id' => $definicion->id,
                'estado_actual_id' => $estadoInicial->id,
                'sujeto_type' => Contrato::class,
                'sujeto_id' => $contrato->id,
                'monto' => $contrato->monto_total,
            ]);

            return $contrato->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public function actualizar(Contrato $contrato, array $datos): Contrato
    {
        $this->exigirBorrador($contrato);

        $contrato->update($datos);

        $contrato->proceso->update(['monto' => $contrato->monto_total]);

        return $contrato->refresh();
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public function agregarItemConvenioPrecio(Contrato $contrato, array $datos): ContratoItemConvenioPrecio
    {
        $this->exigirBorrador($contrato);

        if (! $contrato->tiene_convenio_precio) {
            throw ContratoException::convenioPrecioNoHabilitado();
        }

        return $contrato->itemsConvenioPrecio()->create($datos);
    }

    public function eliminarItemConvenioPrecio(Contrato $contrato, ContratoItemConvenioPrecio $item): void
    {
        $this->exigirBorrador($contrato);

        $item->delete();
    }

    public function vincularProcesoAdquisicion(Contrato $contrato, int $procesoAdquisicionId): Contrato
    {
        return DB::transaction(function () use ($contrato, $procesoAdquisicionId) {
            $anterior = $contrato->proceso_adquisicion_id;

            $contrato->update(['proceso_adquisicion_id' => $procesoAdquisicionId]);

            $this->auditLogger->log(
                action: 'contrato.vincular_proceso_adquisicion',
                auditable: $contrato,
                before: ['proceso_adquisicion_id' => $anterior],
                after: ['proceso_adquisicion_id' => $procesoAdquisicionId],
            );

            return $contrato->refresh();
        });
    }

    public function desvincularProcesoAdquisicion(Contrato $contrato): Contrato
    {
        return DB::transaction(function () use ($contrato) {
            $anterior = $contrato->proceso_adquisicion_id;

            $contrato->update(['proceso_adquisicion_id' => null]);

            $this->auditLogger->log(
                action: 'contrato.desvincular_proceso_adquisicion',
                auditable: $contrato,
                before: ['proceso_adquisicion_id' => $anterior],
                after: ['proceso_adquisicion_id' => null],
            );

            return $contrato->refresh();
        });
    }

    public function vincularLicitacionMercadoPublico(Contrato $contrato, int $licitacionId): Contrato
    {
        return DB::transaction(function () use ($contrato, $licitacionId) {
            $anterior = $contrato->licitacion_mercado_publico_id;

            $contrato->update(['licitacion_mercado_publico_id' => $licitacionId]);

            $this->auditLogger->log(
                action: 'contrato.vincular_licitacion_mercado_publico',
                auditable: $contrato,
                before: ['licitacion_mercado_publico_id' => $anterior],
                after: ['licitacion_mercado_publico_id' => $licitacionId],
            );

            return $contrato->refresh();
        });
    }

    public function desvincularLicitacionMercadoPublico(Contrato $contrato): Contrato
    {
        return DB::transaction(function () use ($contrato) {
            $anterior = $contrato->licitacion_mercado_publico_id;

            $contrato->update(['licitacion_mercado_publico_id' => null]);

            $this->auditLogger->log(
                action: 'contrato.desvincular_licitacion_mercado_publico',
                auditable: $contrato,
                before: ['licitacion_mercado_publico_id' => $anterior],
                after: ['licitacion_mercado_publico_id' => null],
            );

            return $contrato->refresh();
        });
    }

    public function exigirBorrador(Contrato $contrato): void
    {
        $estadoActual = $contrato->proceso->estadoActual->codigo;

        if ($estadoActual !== 'borrador') {
            throw ContratoException::noEditableEnEstado($estadoActual);
        }
    }

    /**
     * Resuelve el proveedor del contrato: si se indica un `proveedorId` ya
     * existente lo usa tal cual; si no, exige `datosProveedor` con un RUT y
     * resuelve/crea por RUT normalizado, completando solo los campos vacíos
     * de un proveedor ya existente — mismo patrón que
     * OrdenCompraMercadoPublicoService::resolverProveedor().
     *
     * @param  array<string, mixed>|null  $datosProveedor
     */
    private function resolverProveedor(?int $proveedorId, ?array $datosProveedor): Proveedor
    {
        if ($proveedorId !== null) {
            return Proveedor::findOrFail($proveedorId);
        }

        $rut = (string) ($datosProveedor['rutproveedor'] ?? '');

        if (Proveedor::normalizarRut($rut) === '') {
            throw new ContratoSinProveedorException;
        }

        $rutNormalizado = Proveedor::normalizarRut($rut);
        $proveedor = Proveedor::where('rutproveedor', $rutNormalizado)->first();

        $camposPayload = collect($datosProveedor)
            ->only(self::CAMPOS_COMPLETABLES_PROVEEDOR)
            ->map(fn ($valor) => is_string($valor) ? trim($valor) : $valor)
            ->filter(fn ($valor) => $valor !== null && $valor !== '')
            ->all();

        if ($proveedor === null) {
            return Proveedor::create([
                'rutproveedor' => $rut,
                'nombre' => $datosProveedor['nombre'] ?? '',
                'estado' => Proveedor::ESTADO_ACTIVO,
                ...$camposPayload,
            ]);
        }

        $camposFaltantes = collect($camposPayload)
            ->filter(fn ($valor, $columna) => in_array($proveedor->{$columna}, [null, ''], true))
            ->all();

        if ($camposFaltantes !== []) {
            $proveedor->fill($camposFaltantes)->save();
        }

        return $proveedor;
    }
}
