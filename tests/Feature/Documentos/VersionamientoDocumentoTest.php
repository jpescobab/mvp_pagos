<?php

use App\Models\Documento;
use App\Models\TipoDocumento;

test('subir una nueva version de un documento no elimina las versiones anteriores', function () {
    $tipo = TipoDocumento::create(['codigo' => 'TEST', 'nombre' => 'Tipo de prueba']);
    $documento = Documento::create(['tipo_documento_id' => $tipo->id]);

    $documento->versiones()->create([
        'numero_version' => 1,
        'ruta_archivo' => 'documentos/1.pdf',
        'nombre_archivo' => 'v1.pdf',
    ]);

    $documento->versiones()->create([
        'numero_version' => 2,
        'ruta_archivo' => 'documentos/2.pdf',
        'nombre_archivo' => 'v2.pdf',
    ]);

    expect($documento->versiones()->count())->toBe(2);
    expect($documento->versiones()->pluck('numero_version')->sort()->values()->all())->toBe([1, 2]);
});
