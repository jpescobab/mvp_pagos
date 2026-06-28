<?php

namespace Database\Seeders;

use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\DefinicionWorkflow;
use App\Models\RequisitoDocumental;
use App\Models\TipoDocumento;
use Illuminate\Database\Seeder;

class RequisitosDocumentalesPagoProveedoresSeeder extends Seeder
{
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

        $reglas = [
            ['codigo' => 'FACTURA', 'tipo_requisito' => 'obligatorio'],
            ['codigo' => 'ACTA_RECEP', 'tipo_requisito' => 'obligatorio'],
            ['codigo' => 'CERT_VIGENCIA', 'tipo_requisito' => 'obligatorio'],
            ['codigo' => 'RESOLUCION', 'tipo_requisito' => 'obligatorio'],
            ['codigo' => 'COMPROBANTE', 'tipo_requisito' => 'obligatorio'],
            ['codigo' => 'ORDEN_COMPRA', 'tipo_requisito' => 'opcional'],
            ['codigo' => 'CONTRATO', 'tipo_requisito' => 'opcional'],
        ];

        foreach ($reglas as $documento) {
            $tipoDocumento = TipoDocumento::where('codigo', $documento['codigo'])->first();

            if ($tipoDocumento === null) {
                continue;
            }

            RequisitoDocumental::firstOrCreate(
                [
                    'conjunto_requisitos_documentales_id' => $conjunto->id,
                    'tipo_documento_id' => $tipoDocumento->id,
                    'modalidad_id' => null,
                ],
                [
                    'definicion_workflow_id' => $definicionWorkflow->id,
                    'tipo_requisito' => $documento['tipo_requisito'],
                    'activo' => true,
                ],
            );
        }
    }
}
