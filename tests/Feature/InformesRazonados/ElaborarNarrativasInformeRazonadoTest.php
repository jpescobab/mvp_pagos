<?php

use App\Models\EjecucionInformeRazonado;
use App\Models\NarrativaInformeRazonado;
use App\Models\User;
use App\Services\InformesRazonados\InformeRazonadoService;
use Database\Seeders\WorkflowInformesRazonadosSeeder;
use Inertia\Testing\AssertableInertia as Assert;

// Los helpers corteReportabilidadDePrueba() y definicionInformeRazonadoDePrueba()
// se definen en InformeRazonadoServiceTest.php y son globales dentro del directorio.

function ejecucionEnElaboracionDePrueba(?User $usuario = null): EjecucionInformeRazonado
{
    $corte = corteReportabilidadDePrueba(['estado' => 'publicado']);
    $definicion = definicionInformeRazonadoDePrueba();

    return app(InformeRazonadoService::class)->iniciarEjecucion($definicion, $corte, $usuario);
}

test('agregar una narrativa con informes.elaborar en una ejecución en elaboración la crea', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);

    $response = $this->actingAs($elaborador)->post(
        route('informes-razonados.ejecuciones.narrativas.store', $ejecucion),
        ['contenido' => 'Análisis del corte']
    );

    $response->assertRedirect();
    expect($ejecucion->narrativas()->count())->toBe(1);
    expect($ejecucion->narrativas()->first()->contenido)->toBe('Análisis del corte');
});

test('agregar una narrativa sin informes.elaborar responde 403 y no crea nada', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $sinPermiso = User::factory()->create();
    $ejecucion = ejecucionEnElaboracionDePrueba();

    $response = $this->actingAs($sinPermiso)->post(
        route('informes-razonados.ejecuciones.narrativas.store', $ejecucion),
        ['contenido' => 'Intento no autorizado']
    );

    $response->assertForbidden();
    expect(NarrativaInformeRazonado::count())->toBe(0);
});

test('editar una narrativa con informes.elaborar en elaboración actualiza su contenido', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);
    $narrativa = app(InformeRazonadoService::class)->agregarNarrativa($ejecucion, 'Original');

    $response = $this->actingAs($elaborador)->patch(
        route('informes-razonados.narrativas.update', $narrativa),
        ['contenido' => 'Corregido']
    );

    $response->assertRedirect();
    expect($narrativa->refresh()->contenido)->toBe('Corregido');
});

test('eliminar una narrativa con informes.elaborar en elaboración la borra', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);
    $narrativa = app(InformeRazonadoService::class)->agregarNarrativa($ejecucion, 'Descartable');

    $response = $this->actingAs($elaborador)->delete(
        route('informes-razonados.narrativas.destroy', $narrativa)
    );

    $response->assertRedirect();
    expect(NarrativaInformeRazonado::whereKey($narrativa->id)->exists())->toBeFalse();
});

test('no se pueden elaborar narrativas cuando la ejecución no está en elaboración', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $servicio = app(InformeRazonadoService::class);
    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);
    $narrativa = $servicio->agregarNarrativa($ejecucion, 'Escrita en elaboración');

    // Mueve la ejecución fuera de en_elaboracion.
    $servicio->enviarARevision($ejecucion, $elaborador);
    expect($ejecucion->proceso->refresh()->estadoActual->codigo)->toBe('en_revision');

    $crear = $this->actingAs($elaborador)->post(
        route('informes-razonados.ejecuciones.narrativas.store', $ejecucion),
        ['contenido' => 'Nueva fuera de elaboración']
    );
    $crear->assertForbidden();

    $editar = $this->actingAs($elaborador)->patch(
        route('informes-razonados.narrativas.update', $narrativa),
        ['contenido' => 'Editada fuera de elaboración']
    );
    $editar->assertForbidden();

    $eliminar = $this->actingAs($elaborador)->delete(
        route('informes-razonados.narrativas.destroy', $narrativa)
    );
    $eliminar->assertForbidden();

    expect($ejecucion->narrativas()->count())->toBe(1);
    expect($narrativa->refresh()->contenido)->toBe('Escrita en elaboración');
});

test('marcar una narrativa como revisada exige informes.aprobar y registra al revisor', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $ejecucion = ejecucionEnElaboracionDePrueba();
    $narrativa = app(InformeRazonadoService::class)->agregarNarrativa($ejecucion, 'Para revisar');

    $sinPermiso = User::factory()->create();
    $sinPermiso->givePermissionTo('informes.elaborar');
    $rechazado = $this->actingAs($sinPermiso)->post(
        route('informes-razonados.narrativas.revisar', $narrativa)
    );
    $rechazado->assertForbidden();
    expect($narrativa->refresh()->revisado_en)->toBeNull();

    $revisor = User::factory()->create();
    $revisor->givePermissionTo('informes.aprobar');
    $aceptado = $this->actingAs($revisor)->post(
        route('informes-razonados.narrativas.revisar', $narrativa)
    );
    $aceptado->assertRedirect();

    $narrativa->refresh();
    expect($narrativa->revisado_por)->toBe($revisor->id);
    expect($narrativa->revisado_en)->not->toBeNull();
});

test('el detalle expone editable=true y los datos de revisión de cada narrativa', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $revisor = User::factory()->create();
    $revisor->givePermissionTo('informes.aprobar');

    $servicio = app(InformeRazonadoService::class);
    $ejecucion = ejecucionEnElaboracionDePrueba();
    $narrativa = $servicio->agregarNarrativa($ejecucion, 'Con revisión');
    $servicio->revisarNarrativa($narrativa, $revisor);

    $response = $this->actingAs($revisor)->get(route('informes-razonados.ejecuciones.show', $ejecucion));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('informes-razonados/ejecuciones/show')
        ->where('ejecucion.editable', true)
        ->where('ejecucion.narrativas.0.revisado_por', $revisor->name)
        ->whereNot('ejecucion.narrativas.0.revisado_en', null)
    );
});

test('el detalle expone editable=false cuando la ejecución salió de elaboración', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $servicio = app(InformeRazonadoService::class);
    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);
    $servicio->enviarARevision($ejecucion, $elaborador);

    $response = $this->actingAs($elaborador)->get(route('informes-razonados.ejecuciones.show', $ejecucion));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('ejecucion.editable', false)
    );
});
