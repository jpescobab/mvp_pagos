<?php

use App\Models\SistemaExterno;
use App\Models\TrabajoIntegracion;
use App\Models\User;
use App\Services\Integraciones\IntegracionExternaService;

/**
 * @param  array<string, mixed>  $overrides
 */
function sistemaExternoDePrueba(array $overrides = []): SistemaExterno
{
    return SistemaExterno::create(array_merge([
        'codigo' => 'SISTEMA_PRUEBA',
        'nombre' => 'Sistema de prueba',
        'tipo_integracion' => 'api',
        'activo' => true,
    ], $overrides));
}

test('IntegracionExternaService inicia un trabajo, registra solicitudes y lo finaliza con su estado', function () {
    $sistema = sistemaExternoDePrueba();
    $servicio = app(IntegracionExternaService::class);

    $trabajo = $servicio->iniciarTrabajo($sistema, 'sincronizacion', 'api');

    expect($trabajo->sistema_externo_id)->toBe($sistema->id);
    expect($trabajo->estado)->toBe('en_progreso');
    expect($trabajo->finalizado_en)->toBeNull();

    $servicio->registrarSolicitud(
        $sistema,
        metodoHttp: 'GET',
        endpoint: '/api/casos',
        estado: 'exitoso',
        payloadRecibido: ['ok' => true],
        codigoRespuestaHttp: 200,
        trabajo: $trabajo,
    );

    $servicio->registrarSolicitud(
        $sistema,
        metodoHttp: 'GET',
        endpoint: '/api/casos/999',
        estado: 'fallido',
        error: 'No encontrado',
        codigoRespuestaHttp: 404,
        trabajo: $trabajo,
    );

    expect($trabajo->refresh()->total_elementos)->toBe(2);
    expect($sistema->solicitudesApiExternas)->toHaveCount(2);

    $finalizado = $servicio->finalizarTrabajo($trabajo, 'completado');

    expect($finalizado->estado)->toBe('completado');
    expect($finalizado->finalizado_en)->not->toBeNull();
});

test('registrarSnapshot crea un snapshot inmutable vinculable, y recapturar la misma referencia crea uno nuevo', function () {
    $sistema = sistemaExternoDePrueba();
    $servicio = app(IntegracionExternaService::class);
    $usuario = User::factory()->create();

    $primero = $servicio->registrarSnapshot(
        $sistema,
        metodoCaptura: 'api',
        payloadCrudo: ['id' => 'abc', 'estado' => 'PENDIENTE'],
        referenciaExterna: 'abc',
        vinculable: $usuario,
        usuario: $usuario,
    );

    expect($primero->hash)->toBe(hash('sha256', json_encode(['id' => 'abc', 'estado' => 'PENDIENTE'], JSON_THROW_ON_ERROR)));
    expect($primero->vinculable_type)->toBe($usuario->getMorphClass());
    expect($primero->vinculable_id)->toBe($usuario->id);
    expect($primero->capturado_por)->toBe($usuario->id);

    $segundo = $servicio->registrarSnapshot(
        $sistema,
        metodoCaptura: 'api',
        payloadCrudo: ['id' => 'abc', 'estado' => 'COMPLETADO'],
        referenciaExterna: 'abc',
    );

    expect($segundo->id)->not->toBe($primero->id);
    expect($primero->refresh()->payload_crudo['estado'])->toBe('PENDIENTE');
    expect($segundo->payload_crudo['estado'])->toBe('COMPLETADO');
});

test('un trabajo en_progreso dentro de su umbral no se considera huérfano', function () {
    $sistema = sistemaExternoDePrueba();

    $trabajo = TrabajoIntegracion::create([
        'sistema_externo_id' => $sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'estado' => 'en_progreso',
        'iniciado_en' => now()->subMinutes(10),
    ]);

    expect($trabajo->esHuerfano())->toBeFalse();
});

test('un trabajo en_progreso que superó el umbral de su tipo se considera huérfano', function () {
    $sistema = sistemaExternoDePrueba();

    $trabajo = TrabajoIntegracion::create([
        'sistema_externo_id' => $sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'estado' => 'en_progreso',
        'iniciado_en' => now()->subMinutes(91),
    ]);

    expect($trabajo->esHuerfano())->toBeTrue();
});

test('un tipo sin umbral explícito usa el umbral default', function () {
    $sistema = sistemaExternoDePrueba();

    $trabajo = TrabajoIntegracion::create([
        'sistema_externo_id' => $sistema->id,
        'tipo' => 'sincronizacion',
        'mecanismo' => 'api',
        'estado' => 'en_progreso',
        'iniciado_en' => now()->subMinutes(121),
    ]);

    expect($trabajo->umbralHuerfanoEnMinutos())->toBe(120);
    expect($trabajo->esHuerfano())->toBeTrue();
});

test('un trabajo ya completado o en error nunca se considera huérfano', function () {
    $sistema = sistemaExternoDePrueba();

    $completado = TrabajoIntegracion::create([
        'sistema_externo_id' => $sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'estado' => 'completado',
        'iniciado_en' => now()->subMinutes(200),
        'finalizado_en' => now()->subMinutes(199),
    ]);
    $enError = TrabajoIntegracion::create([
        'sistema_externo_id' => $sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'estado' => 'error',
        'iniciado_en' => now()->subMinutes(200),
        'finalizado_en' => now()->subMinutes(199),
        'error' => 'Rechazado por el sistema externo',
    ]);

    expect($completado->esHuerfano())->toBeFalse();
    expect($enError->esHuerfano())->toBeFalse();
});

test('marcarHuerfano finaliza el trabajo con estado huerfano y un mensaje explícito', function () {
    $sistema = sistemaExternoDePrueba();
    $servicio = app(IntegracionExternaService::class);

    $trabajo = TrabajoIntegracion::create([
        'sistema_externo_id' => $sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'estado' => 'en_progreso',
        'iniciado_en' => now()->subMinutes(91),
    ]);

    $marcado = $servicio->marcarHuerfano($trabajo);

    expect($marcado->estado)->toBe('huerfano');
    expect($marcado->finalizado_en)->not->toBeNull();
    expect($marcado->error)->toContain('90 minutos');
});

test('expirarSiEsHuerfano no toca un trabajo que sigue dentro del umbral', function () {
    $sistema = sistemaExternoDePrueba();
    $servicio = app(IntegracionExternaService::class);

    $trabajo = TrabajoIntegracion::create([
        'sistema_externo_id' => $sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'estado' => 'en_progreso',
        'iniciado_en' => now()->subMinutes(10),
    ]);

    $resultado = $servicio->expirarSiEsHuerfano($trabajo);

    expect($resultado->estado)->toBe('en_progreso');
    expect($resultado->finalizado_en)->toBeNull();
});

test('expirarHuerfanos marca todos los trabajos huérfanos y respeta los que siguen dentro del umbral', function () {
    $sistema = sistemaExternoDePrueba();
    $servicio = app(IntegracionExternaService::class);

    $huerfano = TrabajoIntegracion::create([
        'sistema_externo_id' => $sistema->id,
        'tipo' => 'verificar_caso',
        'mecanismo' => 'playwright',
        'estado' => 'en_progreso',
        'iniciado_en' => now()->subMinutes(11),
    ]);
    $vigente = TrabajoIntegracion::create([
        'sistema_externo_id' => $sistema->id,
        'tipo' => 'verificar_caso',
        'mecanismo' => 'playwright',
        'estado' => 'en_progreso',
        'iniciado_en' => now()->subMinutes(2),
    ]);

    $total = $servicio->expirarHuerfanos();

    expect($total)->toBe(1);
    expect($huerfano->refresh()->estado)->toBe('huerfano');
    expect($vigente->refresh()->estado)->toBe('en_progreso');
});
