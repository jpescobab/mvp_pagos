<?php

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;

test('validar un documento registra un evento y ese evento define el estado vigente', function () {
    $tipo = DocumentType::create(['codigo' => 'TEST', 'nombre' => 'Tipo de prueba']);
    $documento = Document::create(['document_type_id' => $tipo->id]);
    $usuario = User::factory()->create();

    expect($documento->estadoVigente())->toBe('pendiente');

    $documento->validations()->create([
        'estado' => 'rechazado',
        'observacion' => 'Falta firma',
        'validado_por' => $usuario->id,
        'validado_en' => now(),
    ]);

    expect($documento->refresh()->estadoVigente())->toBe('rechazado');

    $documento->validations()->create([
        'estado' => 'valido',
        'validado_por' => $usuario->id,
        'validado_en' => now(),
    ]);

    expect($documento->refresh()->estadoVigente())->toBe('valido');
    expect($documento->validations)->toHaveCount(2);
});
