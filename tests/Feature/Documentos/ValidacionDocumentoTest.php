<?php

use App\Models\Documento;
use App\Models\TipoDocumento;
use App\Models\User;

test('validar un documento registra un evento y ese evento define el estado vigente', function () {
    $tipo = TipoDocumento::create(['codigo' => 'TEST', 'nombre' => 'Tipo de prueba']);
    $documento = Documento::create(['tipo_documento_id' => $tipo->id]);
    $usuario = User::factory()->create();

    expect($documento->estadoVigente())->toBe('pendiente');

    $documento->validaciones()->create([
        'estado' => 'rechazado',
        'observacion' => 'Falta firma',
        'validado_por' => $usuario->id,
        'validado_en' => now(),
    ]);

    expect($documento->refresh()->estadoVigente())->toBe('rechazado');

    $documento->validaciones()->create([
        'estado' => 'valido',
        'validado_por' => $usuario->id,
        'validado_en' => now(),
    ]);

    expect($documento->refresh()->estadoVigente())->toBe('valido');
    expect($documento->validaciones)->toHaveCount(2);
});
