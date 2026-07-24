<?php

use App\Models\DefinicionInformeRazonado;
use App\Models\EjecucionInformeRazonado;
use App\Models\User;
use App\Services\InformesRazonados\InformeRazonadoService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowInformesRazonadosSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('un usuario con informes.administrar puede crear una definición de informe razonado', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('informes.administrar');

    $response = $this->actingAs($usuario)->post(route('informes-razonados.definiciones.store'), [
        'codigo' => 'EJECUCION-PRESUPUESTARIA',
        'nombre' => 'Ejecución presupuestaria',
    ]);

    $response->assertSessionHasNoErrors();
    expect(DefinicionInformeRazonado::where('codigo', 'EJECUCION-PRESUPUESTARIA')->exists())->toBeTrue();
});

test('iniciar una ejecución sobre un corte publicado crea el registro con su Proceso inicial', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $corte = corteReportabilidadDePrueba(['estado' => 'publicado']);
    $definicion = definicionInformeRazonadoDePrueba();
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(route('informes-razonados.ejecuciones.store'), [
        'definicion_informe_razonado_id' => $definicion->id,
        'corte_reportabilidad_id' => $corte->id,
    ]);

    $response->assertSessionHasNoErrors();

    $ejecucion = EjecucionInformeRazonado::where('corte_reportabilidad_id', $corte->id)->first();
    expect($ejecucion)->not->toBeNull();
    expect($ejecucion->proceso->estadoActual->codigo)->toBe('en_elaboracion');
});

test('iniciar una ejecución sobre un corte en borrador la rechaza', function () {
    $corte = corteReportabilidadDePrueba(['estado' => 'borrador']);
    $definicion = definicionInformeRazonadoDePrueba();
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(route('informes-razonados.ejecuciones.store'), [
        'definicion_informe_razonado_id' => $definicion->id,
        'corte_reportabilidad_id' => $corte->id,
    ]);

    $response->assertSessionHasErrors('corte_reportabilidad_id');
    expect(EjecucionInformeRazonado::count())->toBe(0);
});

test('el ciclo completo enviar a revisión, aprobar y publicar transiciona la ejecución y crea evidencia', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $corte = corteReportabilidadDePrueba(['estado' => 'publicado']);
    $definicion = definicionInformeRazonadoDePrueba();

    $ejecucion = app(InformeRazonadoService::class)
        ->iniciarEjecucion($definicion, $corte, $admin);

    $this->actingAs($admin)
        ->post(route('informes-razonados.ejecuciones.transiciones.store', $ejecucion), ['codigo' => 'enviar_a_revision'])
        ->assertSessionHasNoErrors();
    expect($ejecucion->proceso->refresh()->estadoActual->codigo)->toBe('en_revision');

    $this->actingAs($admin)
        ->post(route('informes-razonados.ejecuciones.transiciones.store', $ejecucion), ['codigo' => 'aprobar', 'comentario' => 'Conforme'])
        ->assertSessionHasNoErrors();
    expect($ejecucion->proceso->refresh()->estadoActual->codigo)->toBe('aprobado');
    expect($ejecucion->aprobaciones)->toHaveCount(1);

    $this->actingAs($admin)
        ->post(route('informes-razonados.ejecuciones.transiciones.store', $ejecucion), ['codigo' => 'publicar'])
        ->assertSessionHasNoErrors();
    expect($ejecucion->proceso->refresh()->estadoActual->codigo)->toBe('publicado');
    expect($ejecucion->snapshots)->toHaveCount(1);
});

test('aprobar sin el permiso informes.aprobar bloquea la transición', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $corte = corteReportabilidadDePrueba(['estado' => 'publicado']);
    $definicion = definicionInformeRazonadoDePrueba();
    $usuario = User::factory()->create();

    $ejecucion = app(InformeRazonadoService::class)
        ->iniciarEjecucion($definicion, $corte, $usuario);

    $this->actingAs($usuario)
        ->post(route('informes-razonados.ejecuciones.transiciones.store', $ejecucion), ['codigo' => 'enviar_a_revision']);

    $response = $this->actingAs($usuario)
        ->post(route('informes-razonados.ejecuciones.transiciones.store', $ejecucion), ['codigo' => 'aprobar']);

    $response->assertSessionHasErrors('transicion');
    expect($ejecucion->proceso->refresh()->estadoActual->codigo)->toBe('en_revision');
});

test('el detalle de una ejecución expone su proceso, transiciones disponibles y contenido', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $corte = corteReportabilidadDePrueba(['estado' => 'publicado']);
    $definicion = definicionInformeRazonadoDePrueba();
    $usuario = User::factory()->create();

    $ejecucion = app(InformeRazonadoService::class)
        ->iniciarEjecucion($definicion, $corte, $usuario);

    $response = $this->actingAs($usuario)->get(route('informes-razonados.ejecuciones.show', $ejecucion));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('informes-razonados/ejecuciones/show')
        ->where('ejecucion.proceso.estado_actual.codigo', 'en_elaboracion')
        ->has('ejecucion.proceso.transiciones_disponibles', 1)
        ->has('ejecucion.metricas', 0)
        ->has('ejecucion.secciones', 0)
    );
});
