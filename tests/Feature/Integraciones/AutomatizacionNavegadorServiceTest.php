<?php

use App\Exceptions\ConectorAutomatizacionNoAutorizadoException;
use App\Models\ConectorAutomatizacionNavegador;
use App\Models\EjecucionAutomatizacionNavegador;
use App\Models\SistemaExterno;
use App\Models\User;
use App\Services\Integraciones\AutomatizacionNavegadorService;

/**
 * @param  array<string, mixed>  $overrides
 */
function conectorAutomatizacionDePrueba(array $overrides = []): ConectorAutomatizacionNavegador
{
    $sistema = SistemaExterno::firstOrCreate(
        ['codigo' => 'SISTEMA_PLAYWRIGHT_PRUEBA'],
        ['nombre' => 'Sistema Playwright de prueba', 'tipo_integracion' => 'playwright'],
    );

    return ConectorAutomatizacionNavegador::create(array_merge([
        'sistema_externo_id' => $sistema->id,
        'codigo' => 'CONECTOR_PRUEBA',
        'nombre' => 'Conector de prueba',
        'activo' => false,
    ], $overrides));
}

test('iniciarEjecucion crea la ejecución cuando el conector está activo y autorizado', function () {
    $usuario = User::factory()->create();
    $conector = conectorAutomatizacionDePrueba([
        'activo' => true,
        'autorizado_por' => $usuario->id,
        'autorizado_en' => now(),
    ]);

    $ejecucion = app(AutomatizacionNavegadorService::class)->iniciarEjecucion($conector, usuario: $usuario);

    expect($ejecucion->conector_automatizacion_navegador_id)->toBe($conector->id);
    expect($ejecucion->iniciado_por)->toBe($usuario->id);
    expect($ejecucion->estado)->toBe('en_progreso');
});

test('iniciarEjecucion rechaza un conector inactivo o sin autorización y no crea ninguna ejecución', function () {
    $conectorInactivo = conectorAutomatizacionDePrueba(['activo' => false, 'codigo' => 'INACTIVO']);

    expect(fn () => app(AutomatizacionNavegadorService::class)->iniciarEjecucion($conectorInactivo))
        ->toThrow(ConectorAutomatizacionNoAutorizadoException::class);

    $conectorSinAutorizar = conectorAutomatizacionDePrueba(['activo' => true, 'codigo' => 'SIN_AUTORIZAR']);

    expect(fn () => app(AutomatizacionNavegadorService::class)->iniciarEjecucion($conectorSinAutorizar))
        ->toThrow(ConectorAutomatizacionNoAutorizadoException::class);

    expect(EjecucionAutomatizacionNavegador::count())->toBe(0);
});

test('registrarPaso y registrarArtefacto persisten asociados a la ejecución y, opcionalmente, a un paso', function () {
    $usuario = User::factory()->create();
    $conector = conectorAutomatizacionDePrueba([
        'activo' => true,
        'autorizado_por' => $usuario->id,
        'autorizado_en' => now(),
    ]);

    $servicio = app(AutomatizacionNavegadorService::class);
    $ejecucion = $servicio->iniciarEjecucion($conector, usuario: $usuario);

    $paso = $servicio->registrarPaso($ejecucion, 1, 'navegar', 'exitoso', ['url' => 'https://example.test']);
    $servicio->registrarArtefacto($ejecucion, 'screenshot', 'storage/automatizacion/paso-1.png', hash('sha256', 'contenido'), $paso);

    expect($ejecucion->pasos)->toHaveCount(1);
    expect($ejecucion->artefactos)->toHaveCount(1);
    expect($ejecucion->artefactos->first()->paso_automatizacion_navegador_id)->toBe($paso->id);

    $finalizada = $servicio->finalizarEjecucion($ejecucion, 'completado', resumenResultado: 'Todo OK');

    expect($finalizada->estado)->toBe('completado');
    expect($finalizada->finalizado_en)->not->toBeNull();
    expect($finalizada->resumen_resultado)->toBe('Todo OK');
});
