<?php

use App\Exceptions\TransicionWorkflowException;
use App\Models\EjecucionInformeRazonado;
use App\Models\User;
use App\Services\InformesRazonados\InformeRazonadoService;
use Database\Seeders\WorkflowInformesRazonadosSeeder;

// Los helpers corteReportabilidadDePrueba() y definicionInformeRazonadoDePrueba()
// se definen en InformeRazonadoServiceTest.php y son globales dentro del directorio.

test('iniciar una ejecución sin informes.elaborar responde 403 y no crea ninguna ejecución', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $usuario = User::factory()->create();

    $corte = corteReportabilidadDePrueba(['estado' => 'publicado']);
    $definicion = definicionInformeRazonadoDePrueba();

    $response = $this->actingAs($usuario)->post(route('informes-razonados.ejecuciones.store'), [
        'definicion_informe_razonado_id' => $definicion->id,
        'corte_reportabilidad_id' => $corte->id,
    ]);

    $response->assertForbidden();
    expect(EjecucionInformeRazonado::count())->toBe(0);
});

test('la transición enviar_a_revision exige informes.elaborar', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $corte = corteReportabilidadDePrueba(['estado' => 'publicado']);
    $definicion = definicionInformeRazonadoDePrueba();
    $servicio = app(InformeRazonadoService::class);

    $ejecucion = $servicio->iniciarEjecucion($definicion, $corte);

    $sinPermiso = User::factory()->create();
    expect(fn () => $servicio->enviarARevision($ejecucion, $sinPermiso))
        ->toThrow(TransicionWorkflowException::class);
    expect($ejecucion->proceso->refresh()->estadoActual->codigo)->toBe('en_elaboracion');

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');
    $servicio->enviarARevision($ejecucion, $elaborador);
    expect($ejecucion->proceso->refresh()->estadoActual->codigo)->toBe('en_revision');
});

test('la transición rechazar exige informes.aprobar', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $corte = corteReportabilidadDePrueba(['estado' => 'publicado']);
    $definicion = definicionInformeRazonadoDePrueba();
    $servicio = app(InformeRazonadoService::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $ejecucion = $servicio->iniciarEjecucion($definicion, $corte, $elaborador);
    $servicio->enviarARevision($ejecucion, $elaborador);

    $sinPermiso = User::factory()->create();
    expect(fn () => $servicio->rechazar($ejecucion, 'No cumple', $sinPermiso))
        ->toThrow(TransicionWorkflowException::class);
    expect($ejecucion->proceso->refresh()->estadoActual->codigo)->toBe('en_revision');
    expect($ejecucion->aprobaciones()->count())->toBe(0);

    $revisor = User::factory()->create();
    $revisor->givePermissionTo('informes.aprobar');
    $servicio->rechazar($ejecucion, 'No cumple', $revisor);
    expect($ejecucion->proceso->refresh()->estadoActual->codigo)->toBe('rechazado');
    expect($ejecucion->aprobaciones()->where('decision', 'rechazado')->count())->toBe(1);
});
