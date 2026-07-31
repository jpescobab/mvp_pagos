<?php

use App\Models\TipoDocumento;
use Database\Seeders\TiposDocumentoSeeder;

test('el seeder crea los 16 tipos documentales reales', function () {
    $this->seed(TiposDocumentoSeeder::class);

    expect(TipoDocumento::count())->toBe(16);
    expect(TipoDocumento::pluck('codigo')->sort()->values()->all())->toBe([
        'ACTA_RECEP',
        'BASES_LICITACION',
        'CDP',
        'CERT_VIGENCIA',
        'COMPROBANTE',
        'CONTRATO',
        'FACTURA',
        'GARANTIA',
        'INFORME_JUSTIFICACION_TRATO_DIRECTO',
        'NOTA_CREDITO',
        'NOTA_DEBITO',
        'ORDEN_COMPRA',
        'OTRO',
        'PRESUPUESTO_CGU',
        'RESOLUCION',
        'RESOLUCION_ADJUDICACION',
    ]);
});

test('FACTURA es obligatorio por defecto y el resto no', function () {
    $this->seed(TiposDocumentoSeeder::class);

    expect(TipoDocumento::where('codigo', 'FACTURA')->first()->es_obligatorio)->toBeTrue();
    expect(TipoDocumento::where('codigo', 'OTRO')->first()->es_obligatorio)->toBeFalse();
});
