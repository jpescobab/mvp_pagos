<?php

namespace Database\Seeders;

use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\DefinicionWorkflow;
use App\Models\ModalidadAdquisicion;
use App\Models\RequisitoDocumental;
use App\Models\TipoDocumento;
use Illuminate\Database\Seeder;

class RequisitosDocumentalesAdquisicionesSeeder extends Seeder
{
    public function run(): void
    {
        $definicionWorkflow = DefinicionWorkflow::where('codigo', 'adquisiciones')->first();

        if ($definicionWorkflow === null) {
            return;
        }

        $conjunto = ConjuntoRequisitosDocumentales::firstOrCreate(
            ['codigo' => 'adquisiciones'],
            ['nombre' => 'Requisitos documentales de Adquisiciones', 'activo' => true],
        );

        $reglas = [
            'LICITACION_PUBLICA' => [
                ['codigo' => 'BASES_LICITACION', 'tipo_requisito' => 'obligatorio'],
                ['codigo' => 'RESOLUCION_ADJUDICACION', 'tipo_requisito' => 'obligatorio'],
                ['codigo' => 'CONTRATO', 'tipo_requisito' => 'obligatorio'],
                ['codigo' => 'GARANTIA', 'tipo_requisito' => 'obligatorio'],
                ['codigo' => 'ACTA_RECEP', 'tipo_requisito' => 'obligatorio'],
            ],
            'LICITACION_PRIVADA' => [
                ['codigo' => 'BASES_LICITACION', 'tipo_requisito' => 'obligatorio'],
                ['codigo' => 'RESOLUCION_ADJUDICACION', 'tipo_requisito' => 'obligatorio'],
                ['codigo' => 'CONTRATO', 'tipo_requisito' => 'obligatorio'],
                ['codigo' => 'GARANTIA', 'tipo_requisito' => 'opcional'],
                ['codigo' => 'ACTA_RECEP', 'tipo_requisito' => 'obligatorio'],
            ],
            'TRATO_DIRECTO' => [
                ['codigo' => 'RESOLUCION_ADJUDICACION', 'tipo_requisito' => 'obligatorio'],
                ['codigo' => 'CONTRATO', 'tipo_requisito' => 'obligatorio'],
                ['codigo' => 'ACTA_RECEP', 'tipo_requisito' => 'obligatorio'],
            ],
            'CONVENIO_MARCO' => [
                ['codigo' => 'CONTRATO', 'tipo_requisito' => 'obligatorio'],
                ['codigo' => 'ACTA_RECEP', 'tipo_requisito' => 'obligatorio'],
            ],
        ];

        foreach ($reglas as $modalidadCodigo => $documentos) {
            $modalidad = ModalidadAdquisicion::where('codigo', $modalidadCodigo)->first();

            if ($modalidad === null) {
                continue;
            }

            foreach ($documentos as $documento) {
                $tipoDocumento = TipoDocumento::where('codigo', $documento['codigo'])->first();

                if ($tipoDocumento === null) {
                    continue;
                }

                RequisitoDocumental::firstOrCreate(
                    [
                        'conjunto_requisitos_documentales_id' => $conjunto->id,
                        'tipo_documento_id' => $tipoDocumento->id,
                        'modalidad_id' => $modalidad->id,
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
}
