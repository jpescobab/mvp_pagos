<?php

use App\Models\Document;
use App\Models\DocumentType;

test('subir una nueva version de un documento no elimina las versiones anteriores', function () {
    $tipo = DocumentType::create(['codigo' => 'TEST', 'nombre' => 'Tipo de prueba']);
    $documento = Document::create(['document_type_id' => $tipo->id]);

    $documento->versions()->create([
        'version_number' => 1,
        'file_path' => 'documentos/1.pdf',
        'file_name' => 'v1.pdf',
    ]);

    $documento->versions()->create([
        'version_number' => 2,
        'file_path' => 'documentos/2.pdf',
        'file_name' => 'v2.pdf',
    ]);

    expect($documento->versions()->count())->toBe(2);
    expect($documento->versions()->pluck('version_number')->sort()->values()->all())->toBe([1, 2]);
});
