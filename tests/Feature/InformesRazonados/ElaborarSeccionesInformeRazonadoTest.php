<?php

use App\Models\NarrativaInformeRazonado;
use App\Models\SeccionInformeRazonado;
use App\Models\User;
use App\Services\InformesRazonados\InformeRazonadoService;
use Database\Seeders\WorkflowInformesRazonadosSeeder;

// Los helpers corteReportabilidadDePrueba(), definicionInformeRazonadoDePrueba()
// y ejecucionEnElaboracionDePrueba() son globales dentro del directorio
// (definidos en InformeRazonadoServiceTest.php y ElaborarNarrativasInformeRazonadoTest.php).

test('agregar una sección con informes.elaborar en una ejecución en elaboración la crea', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);

    $response = $this->actingAs($elaborador)->post(
        route('informes-razonados.ejecuciones.secciones.store', $ejecucion),
        ['codigo' => 'INTRO', 'titulo' => 'Introducción', 'orden' => 1]
    );

    $response->assertRedirect();
    expect($ejecucion->secciones()->count())->toBe(1);
    expect($ejecucion->secciones()->first()->titulo)->toBe('Introducción');
});

test('agregar una sección sin informes.elaborar responde 403 y no crea nada', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $sinPermiso = User::factory()->create();
    $ejecucion = ejecucionEnElaboracionDePrueba();

    $response = $this->actingAs($sinPermiso)->post(
        route('informes-razonados.ejecuciones.secciones.store', $ejecucion),
        ['codigo' => 'X', 'titulo' => 'No autorizada']
    );

    $response->assertForbidden();
    expect(SeccionInformeRazonado::count())->toBe(0);
});

test('editar una sección con informes.elaborar actualiza título y orden', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);
    $seccion = app(InformeRazonadoService::class)->agregarSeccion($ejecucion, 'C1', 'Viejo', 0);

    $response = $this->actingAs($elaborador)->patch(
        route('informes-razonados.secciones.update', $seccion),
        ['titulo' => 'Nuevo', 'orden' => 5]
    );

    $response->assertRedirect();
    $seccion->refresh();
    expect($seccion->titulo)->toBe('Nuevo');
    expect($seccion->orden)->toBe(5);
});

test('eliminar una sección con informes.elaborar la borra', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);
    $seccion = app(InformeRazonadoService::class)->agregarSeccion($ejecucion, 'C1', 'Descartable', 0);

    $response = $this->actingAs($elaborador)->delete(
        route('informes-razonados.secciones.destroy', $seccion)
    );

    $response->assertRedirect();
    expect(SeccionInformeRazonado::whereKey($seccion->id)->exists())->toBeFalse();
});

test('no se pueden elaborar secciones cuando la ejecución no está en elaboración', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $servicio = app(InformeRazonadoService::class);
    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);
    $seccion = $servicio->agregarSeccion($ejecucion, 'C1', 'En elaboración', 0);

    $servicio->enviarARevision($ejecucion, $elaborador);
    expect($ejecucion->proceso->refresh()->estadoActual->codigo)->toBe('en_revision');

    $crear = $this->actingAs($elaborador)->post(
        route('informes-razonados.ejecuciones.secciones.store', $ejecucion),
        ['codigo' => 'C2', 'titulo' => 'Nueva fuera de elaboración']
    );
    $crear->assertForbidden();

    $editar = $this->actingAs($elaborador)->patch(
        route('informes-razonados.secciones.update', $seccion),
        ['titulo' => 'Editada fuera de elaboración']
    );
    $editar->assertForbidden();

    $eliminar = $this->actingAs($elaborador)->delete(
        route('informes-razonados.secciones.destroy', $seccion)
    );
    $eliminar->assertForbidden();

    expect($ejecucion->secciones()->count())->toBe(1);
    expect($seccion->refresh()->titulo)->toBe('En elaboración');
});

test('eliminar una sección elimina en cascada su contenido asignado', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $servicio = app(InformeRazonadoService::class);
    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);

    $seccion = $servicio->agregarSeccion($ejecucion, 'C1', 'Con contenido', 0);
    $asignada = $servicio->agregarNarrativa($ejecucion, 'Dentro de la sección', false, $seccion);
    $libre = $servicio->agregarNarrativa($ejecucion, 'Sin sección');

    $this->actingAs($elaborador)
        ->delete(route('informes-razonados.secciones.destroy', $seccion))
        ->assertRedirect();

    expect(SeccionInformeRazonado::whereKey($seccion->id)->exists())->toBeFalse();
    expect(NarrativaInformeRazonado::whereKey($asignada->id)->exists())->toBeFalse();
    expect(NarrativaInformeRazonado::whereKey($libre->id)->exists())->toBeTrue();
});

test('crear una narrativa con una sección de la misma ejecución la asocia', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $servicio = app(InformeRazonadoService::class);
    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);
    $seccion = $servicio->agregarSeccion($ejecucion, 'C1', 'Destino', 0);

    $response = $this->actingAs($elaborador)->post(
        route('informes-razonados.ejecuciones.narrativas.store', $ejecucion),
        ['contenido' => 'En la sección', 'seccion_informe_razonado_id' => $seccion->id]
    );

    $response->assertRedirect();
    expect($ejecucion->narrativas()->first()->seccion_informe_razonado_id)->toBe($seccion->id);
});

test('crear una narrativa con una sección de otra ejecución es rechazado por validación', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $servicio = app(InformeRazonadoService::class);
    $ejecucionA = ejecucionEnElaboracionDePrueba($elaborador);
    $ejecucionB = ejecucionEnElaboracionDePrueba($elaborador);
    $seccionDeB = $servicio->agregarSeccion($ejecucionB, 'C1', 'De otra', 0);

    $response = $this->actingAs($elaborador)->post(
        route('informes-razonados.ejecuciones.narrativas.store', $ejecucionA),
        ['contenido' => 'Intento cruzado', 'seccion_informe_razonado_id' => $seccionDeB->id]
    );

    $response->assertSessionHasErrors('seccion_informe_razonado_id');
    expect($ejecucionA->narrativas()->count())->toBe(0);
});
