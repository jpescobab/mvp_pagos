<?php

namespace Database\Seeders;

use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\DefinicionWorkflow;
use App\Models\RequisitoDocumental;
use App\Models\TipoDocumento;
use App\Models\TipoProcesoPago;
use Illuminate\Database\Seeder;

class RequisitosDocumentalesPagoProveedoresSeeder extends Seeder
{
    /**
     * Documentos universales: aplican a cualquier caso, clasificado o no.
     * FACTURA se mantiene universal (no varía por tipo, ni siquiera para
     * ANTICIPO) porque las transiciones `aprobar_finanzas`/`aprobar_zonal`
     * ya la exigen de forma incondicional vía `documentos_requeridos` — el
     * checklist debe reflejar ese mismo requisito, no contradecirlo.
     *
     * @var list<array{codigo: string, tipo_requisito: string}>
     */
    private const REGLAS_UNIVERSALES = [
        ['codigo' => 'FACTURA', 'tipo_requisito' => 'obligatorio'],
        ['codigo' => 'COMPROBANTE', 'tipo_requisito' => 'obligatorio'],
    ];

    /**
     * Documentos que antes eran universales y ahora varían por tipo de
     * proceso de pago — sus filas universales anteriores se desactivan.
     *
     * @var list<string>
     */
    private const CODIGOS_YA_NO_UNIVERSALES = ['ACTA_RECEP', 'CERT_VIGENCIA', 'RESOLUCION', 'ORDEN_COMPRA', 'CONTRATO'];

    /**
     * Matriz por tipo de proceso de pago (FACTURA y COMPROBANTE quedan
     * fuera de aquí por ser universales, ver REGLAS_UNIVERSALES).
     *
     * @var array<string, list<array{codigo: string, tipo_requisito: string}>>
     */
    private const MATRIZ_POR_TIPO = [
        'COMPRA' => [
            ['codigo' => 'ORDEN_COMPRA', 'tipo_requisito' => 'obligatorio'],
            ['codigo' => 'ACTA_RECEP', 'tipo_requisito' => 'obligatorio'],
            ['codigo' => 'CERT_VIGENCIA', 'tipo_requisito' => 'opcional'],
            ['codigo' => 'RESOLUCION', 'tipo_requisito' => 'opcional'],
        ],
        'CONTRATO' => [
            ['codigo' => 'CONTRATO', 'tipo_requisito' => 'obligatorio'],
            ['codigo' => 'ACTA_RECEP', 'tipo_requisito' => 'obligatorio'],
            ['codigo' => 'CERT_VIGENCIA', 'tipo_requisito' => 'obligatorio'],
            ['codigo' => 'RESOLUCION', 'tipo_requisito' => 'opcional'],
        ],
        'CONVENIO' => [
            ['codigo' => 'RESOLUCION', 'tipo_requisito' => 'obligatorio'],
            ['codigo' => 'ACTA_RECEP', 'tipo_requisito' => 'opcional'],
            ['codigo' => 'CERT_VIGENCIA', 'tipo_requisito' => 'opcional'],
        ],
        'REEMBOLSO' => [
            ['codigo' => 'RESOLUCION', 'tipo_requisito' => 'opcional'],
        ],
        'ANTICIPO' => [
            ['codigo' => 'RESOLUCION', 'tipo_requisito' => 'obligatorio'],
        ],
        'OTRO' => [
            ['codigo' => 'ORDEN_COMPRA', 'tipo_requisito' => 'opcional'],
            ['codigo' => 'CONTRATO', 'tipo_requisito' => 'opcional'],
            ['codigo' => 'ACTA_RECEP', 'tipo_requisito' => 'obligatorio'],
            ['codigo' => 'CERT_VIGENCIA', 'tipo_requisito' => 'obligatorio'],
            ['codigo' => 'RESOLUCION', 'tipo_requisito' => 'obligatorio'],
        ],
    ];

    public function run(): void
    {
        $definicionWorkflow = DefinicionWorkflow::where('codigo', 'pago_proveedores')->first();

        if ($definicionWorkflow === null) {
            return;
        }

        $conjunto = ConjuntoRequisitosDocumentales::firstOrCreate(
            ['codigo' => 'pago_proveedores'],
            ['nombre' => 'Requisitos documentales de Pago de Proveedores', 'activo' => true],
        );

        RequisitoDocumental::where('conjunto_requisitos_documentales_id', $conjunto->id)
            ->whereNull('tipo_proceso_pago_id')
            ->whereIn('tipo_documento_id', TipoDocumento::whereIn('codigo', self::CODIGOS_YA_NO_UNIVERSALES)->pluck('id'))
            ->update(['activo' => false]);

        foreach (self::REGLAS_UNIVERSALES as $regla) {
            $this->crearRequisito($conjunto, $definicionWorkflow, $regla, null);
        }

        foreach (self::MATRIZ_POR_TIPO as $codigoTipoProceso => $reglas) {
            $tipoProcesoPago = TipoProcesoPago::where('codigo', $codigoTipoProceso)->first();

            if ($tipoProcesoPago === null) {
                continue;
            }

            foreach ($reglas as $regla) {
                $this->crearRequisito($conjunto, $definicionWorkflow, $regla, $tipoProcesoPago->id);
            }
        }
    }

    /**
     * @param  array{codigo: string, tipo_requisito: string}  $regla
     */
    private function crearRequisito(
        ConjuntoRequisitosDocumentales $conjunto,
        DefinicionWorkflow $definicionWorkflow,
        array $regla,
        ?int $tipoProcesoPagoId,
    ): void {
        $tipoDocumento = TipoDocumento::where('codigo', $regla['codigo'])->first();

        if ($tipoDocumento === null) {
            return;
        }

        RequisitoDocumental::updateOrCreate(
            [
                'conjunto_requisitos_documentales_id' => $conjunto->id,
                'tipo_documento_id' => $tipoDocumento->id,
                'modalidad_id' => null,
                'tipo_proceso_pago_id' => $tipoProcesoPagoId,
            ],
            [
                'definicion_workflow_id' => $definicionWorkflow->id,
                'tipo_requisito' => $regla['tipo_requisito'],
                'activo' => true,
            ],
        );
    }
}
