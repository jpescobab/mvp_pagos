<?php

use App\Models\SistemaExterno;
use App\Models\TrabajoIntegracion;

beforeEach(function () {
    $this->sistema = SistemaExterno::create([
        'codigo' => 'SISTEMA_PRUEBA',
        'nombre' => 'Sistema de prueba',
        'tipo_integracion' => 'playwright',
        'activo' => true,
    ]);
});

test('el comando marca como huerfano solo los trabajos que superaron su umbral', function () {
    $huerfano = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'estado' => 'en_progreso',
        'iniciado_en' => now()->subMinutes(91),
    ]);
    $vigente = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'estado' => 'en_progreso',
        'iniciado_en' => now()->subMinutes(5),
    ]);
    $yaCompletado = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'estado' => 'completado',
        'iniciado_en' => now()->subMinutes(200),
        'finalizado_en' => now()->subMinutes(199),
        'total_elementos' => 5,
    ]);

    $this->artisan('trabajos-integracion:expirar-huerfanos')
        ->expectsOutputToContain('Trabajos marcados como huérfanos: 1')
        ->assertSuccessful();

    expect($huerfano->refresh()->estado)->toBe('huerfano');
    expect($vigente->refresh()->estado)->toBe('en_progreso');
    expect($yaCompletado->refresh()->estado)->toBe('completado');
});

test('el comando no hace nada si no hay ningún trabajo huérfano', function () {
    TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'verificar_caso',
        'mecanismo' => 'playwright',
        'estado' => 'en_progreso',
        'iniciado_en' => now()->subMinutes(2),
    ]);

    $this->artisan('trabajos-integracion:expirar-huerfanos')
        ->expectsOutputToContain('Trabajos marcados como huérfanos: 0')
        ->assertSuccessful();
});
