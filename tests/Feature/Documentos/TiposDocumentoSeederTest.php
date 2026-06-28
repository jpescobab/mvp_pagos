<?php

use App\Models\TipoDocumento;
use Database\Seeders\TiposDocumentoSeeder;

test('el seeder crea los 13 tipos documentales reales', function () {
    $this->seed(TiposDocumentoSeeder::class);

    expect(TipoDocumento::count())->toBe(13);
    expect(TipoDocumento::pluck('codigo')->sort()->values()->all())->toBe([
        'ACTA_RECEP',
        'BASES_LICITACION',
        'CERT_VIGENCIA',
        'COMPROBANTE',
        'CONTRATO',
        'FACTURA',
        'GARANTIA',
        'NOTA_CREDITO',
        'NOTA_DEBITO',
        'ORDEN_COMPRA',
        'OTRO',
        'RESOLUCION',
        'RESOLUCION_ADJUDICACION',
    ]);
});

test('FACTURA es obligatorio por defecto y el resto no', function () {
    $this->seed(TiposDocumentoSeeder::class);

    expect(TipoDocumento::where('codigo', 'FACTURA')->first()->es_obligatorio)->toBeTrue();
    expect(TipoDocumento::where('codigo', 'OTRO')->first()->es_obligatorio)->toBeFalse();
});
