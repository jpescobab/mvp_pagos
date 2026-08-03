<?php

namespace Database\Seeders;

use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\DefinicionWorkflow;
use App\Models\RequisitoDocumental;
use App\Models\TipoDocumento;
use Illuminate\Database\Seeder;

class RequisitosDocumentalesContratosSeeder extends Seeder
{
    public function run(): void
    {
        $definicionWorkflow = DefinicionWorkflow::where('codigo', 'contratos')->first();

        if ($definicionWorkflow === null) {
            return;
        }

        $conjunto = ConjuntoRequisitosDocumentales::firstOrCreate(
            ['codigo' => 'contratos'],
            ['nombre' => 'Requisitos documentales de Contratos', 'activo' => true],
        );

        $tipoDocumento = TipoDocumento::where('codigo', 'CONTRATO')->first();

        if ($tipoDocumento === null) {
            return;
        }

        RequisitoDocumental::firstOrCreate(
            [
                'conjunto_requisitos_documentales_id' => $conjunto->id,
                'tipo_documento_id' => $tipoDocumento->id,
                'modalidad_id' => null,
            ],
            [
                'definicion_workflow_id' => $definicionWorkflow->id,
                'tipo_requisito' => 'obligatorio',
                'activo' => true,
            ],
        );
    }
}
