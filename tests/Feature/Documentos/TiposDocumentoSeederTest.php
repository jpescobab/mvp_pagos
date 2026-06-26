<?php

use App\Models\TipoDocumento;
use Database\Seeders\TiposDocumentoSeeder;

test('el seeder crea los 10 tipos documentales reales', function () {
    $this->seed(TiposDocumentoSeeder::class);

    expect(TipoDocumento::count())->toBe(10);
    expect(TipoDocumento::pluck('codigo')->sort()->values()->all())->toBe([
        'ACTA_RECEP',
        'CERT_VIGENCIA',
        'COMPROBANTE',
        'CONTRATO',
        'FACTURA',
        'NOTA_CREDITO',
        'NOTA_DEBITO',
        'ORDEN_COMPRA',
        'OTRO',
        'RESOLUCION',
    ]);
});

test('FACTURA es obligatorio por defecto y el resto no', function () {
    $this->seed(TiposDocumentoSeeder::class);

    expect(TipoDocumento::where('codigo', 'FACTURA')->first()->es_obligatorio)->toBeTrue();
    expect(TipoDocumento::where('codigo', 'OTRO')->first()->es_obligatorio)->toBeFalse();
});
