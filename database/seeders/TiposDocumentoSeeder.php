<?php

namespace Database\Seeders;

use App\Models\TipoDocumento;
use Illuminate\Database\Seeder;

class TiposDocumentoSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            ['codigo' => 'FACTURA',       'nombre' => 'Factura',                    'descripcion' => 'Factura electrónica de proveedor',          'es_obligatorio' => true],
            ['codigo' => 'ORDEN_COMPRA',  'nombre' => 'Orden de Compra',            'descripcion' => 'Orden de compra vinculada al pago',          'es_obligatorio' => false],
            ['codigo' => 'CONTRATO',      'nombre' => 'Contrato',                   'descripcion' => 'Contrato con el proveedor',                  'es_obligatorio' => false],
            ['codigo' => 'ACTA_RECEP',    'nombre' => 'Acta de Recepción',          'descripcion' => 'Acta de conformidad de bienes/servicios',    'es_obligatorio' => false],
            ['codigo' => 'CERT_VIGENCIA', 'nombre' => 'Certificado de Vigencia',    'descripcion' => 'Certificado de vigencia del proveedor',      'es_obligatorio' => false],
            ['codigo' => 'RESOLUCION',    'nombre' => 'Resolución de Pago',         'descripcion' => 'Resolución exenta que autoriza el pago',     'es_obligatorio' => false],
            ['codigo' => 'COMPROBANTE',   'nombre' => 'Comprobante de Pago',        'descripcion' => 'Comprobante de transferencia o cheque',      'es_obligatorio' => false],
            ['codigo' => 'NOTA_CREDITO',  'nombre' => 'Nota de Crédito',            'descripcion' => 'Nota de crédito electrónica',               'es_obligatorio' => false],
            ['codigo' => 'NOTA_DEBITO',   'nombre' => 'Nota de Débito',             'descripcion' => 'Nota de débito electrónica',                'es_obligatorio' => false],
            ['codigo' => 'OTRO',          'nombre' => 'Otro Documento',             'descripcion' => 'Documento complementario no clasificado',    'es_obligatorio' => false],
        ];

        foreach ($tipos as $tipo) {
            TipoDocumento::firstOrCreate(
                ['codigo' => $tipo['codigo']],
                array_merge($tipo, ['activo' => true])
            );
        }
    }
}
