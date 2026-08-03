<?php

namespace Database\Seeders;

use App\Models\Contrato;
use App\Models\LicitacionMercadoPublico;
use App\Services\Contratos\ContratoService;
use Illuminate\Database\Seeder;

/**
 * Snapshot de los 70 contratos reales de la planilla institucional
 * "lista-contratos", importados el 2026-08-01. Existe solo para poblar un
 * entorno de desarrollo sin depender de la planilla original — NO reemplaza
 * ninguna vía de importación en producción (este módulo no tiene una, ver
 * design.md del change `modulo-contratos`).
 *
 * Todos los contratos quedan creados en `borrador`, independientemente del
 * `estado_origen` que traían en la planilla (PENDIENTE/APROBADO): avanzarlos
 * de estado exige pasar por `TransicionWorkflowService::execute()`, y la
 * transición a `aprobado` exige el documento CONTRATO cargado y validado —
 * requisito que no se puede satisfacer de forma masiva sin los PDF reales.
 * Queda como trabajo manual posterior desde la UI.
 *
 * Cada contrato se vincula (informativamente, sin gobernar workflow) a la
 * `LicitacionMercadoPublico` cuyo código coincide con su `id_proceso_mp`,
 * cuando esa licitación ya fue sembrada por
 * `LicitacionesMercadoPublicoDesarrolloSeeder` (26 de los 70 códigos de la
 * planilla no tienen coincidencia en la API pública de Mercado Público —
 * ver `contratos:importar-licitaciones` — y quedan sin vincular).
 */
class ContratosDesarrolloSeeder extends Seeder
{
    public function run(ContratoService $servicio): void
    {
        $ruta = database_path('seeders/fixtures/contratos_2026.json');

        if (! file_exists($ruta)) {
            return;
        }

        /** @var list<array<string, mixed>> $filas */
        $filas = json_decode((string) file_get_contents($ruta), true) ?? [];

        foreach ($filas as $fila) {
            if (Contrato::where('id_institucional', $fila['id_institucional'])->exists()) {
                continue;
            }

            $datosProveedor = $fila['proveedor'];

            $datos = [
                'id_institucional' => $fila['id_institucional'],
                'modalidad_compra' => $fila['modalidad_compra'],
                'id_proceso_mp' => $fila['id_proceso_mp'],
                'tipo_contrato' => $fila['tipo_contrato'],
                'referencia' => $fila['referencia'],
                'fecha_inicio_vigencia' => $fila['fecha_inicio_vigencia'],
                'fecha_fin_vigencia' => $fila['fecha_fin_vigencia'],
                'materia' => $fila['materia'],
                'submateria' => $fila['submateria'],
                'tiene_convenio_precio' => $fila['tiene_convenio_precio'],
                'tiene_calendario_pago' => $fila['tiene_calendario_pago'],
                'periodicidad_pago' => $fila['periodicidad_pago'],
                'monto_total' => $fila['monto_total'],
            ];

            $contrato = $servicio->crear($datos, $datosProveedor);

            if ($fila['id_proceso_mp'] !== null) {
                $licitacion = LicitacionMercadoPublico::where('codigo', $fila['id_proceso_mp'])->first();

                if ($licitacion !== null) {
                    $servicio->vincularLicitacionMercadoPublico($contrato, $licitacion->id);
                }
            }
        }
    }
}
