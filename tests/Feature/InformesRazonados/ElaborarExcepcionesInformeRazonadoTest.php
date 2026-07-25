<?php

use App\Models\ExcepcionInformeRazonado;
use App\Models\User;
use App\Services\InformesRazonados\InformeRazonadoService;
use Database\Seeders\WorkflowInformesRazonadosSeeder;

// Los helpers corteReportabilidadDePrueba(), definicionInformeRazonadoDePrueba()
// y ejecucionEnElaboracionDePrueba() son globales dentro del directorio.

test('agregar una excepción con informes.elaborar en una ejecución en elaboración la crea', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);

    $response = $this->actingAs($elaborador)->post(
        route('informes-razonados.ejecuciones.excepciones.store', $ejecucion),
        [
            'codigo' => 'EXCL-1',
            'descripcion' => 'El caso X quedó excluido por falta de documento',
            'severidad' => 'critico',
        ]
    );

    $response->assertRedirect();
    expect($ejecucion->excepciones()->count())->toBe(1);
    $excepcion = $ejecucion->excepciones()->first();
    expect($excepcion->severidad)->toBe('critico');
    expect($excepcion->descripcion)->toBe('El caso X quedó excluido por falta de documento');
});

test('agregar una excepción sin informes.elaborar responde 403 y no crea nada', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $sinPermiso = User::factory()->create();
    $ejecucion = ejecucionEnElaboracionDePrueba();

    $response = $this->actingAs($sinPermiso)->post(
        route('informes-razonados.ejecuciones.excepciones.store', $ejecucion),
        ['codigo' => 'X', 'descripcion' => 'No autorizada', 'severidad' => 'info']
    );

    $response->assertForbidden();
    expect(ExcepcionInformeRazonado::count())->toBe(0);
});

test('editar una excepción con informes.elaborar actualiza descripción y severidad', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);
    $excepcion = app(InformeRazonadoService::class)->agregarExcepcion($ejecucion, 'C1', 'Vieja', 'info');

    $response = $this->actingAs($elaborador)->patch(
        route('informes-razonados.excepciones.update', $excepcion),
        ['descripcion' => 'Nueva', 'severidad' => 'advertencia']
    );

    $response->assertRedirect();
    $excepcion->refresh();
    expect($excepcion->descripcion)->toBe('Nueva');
    expect($excepcion->severidad)->toBe('advertencia');
});

test('eliminar una excepción con informes.elaborar la borra', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);
    $excepcion = app(InformeRazonadoService::class)->agregarExcepcion($ejecucion, 'C1', 'Descartable', 'info');

    $response = $this->actingAs($elaborador)->delete(
        route('informes-razonados.excepciones.destroy', $excepcion)
    );

    $response->assertRedirect();
    expect(ExcepcionInformeRazonado::whereKey($excepcion->id)->exists())->toBeFalse();
});

test('no se pueden elaborar excepciones cuando la ejecución no está en elaboración', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $servicio = app(InformeRazonadoService::class);
    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);
    $excepcion = $servicio->agregarExcepcion($ejecucion, 'C1', 'En elaboración', 'info');

    $servicio->enviarARevision($ejecucion, $elaborador);
    expect($ejecucion->proceso->refresh()->estadoActual->codigo)->toBe('en_revision');

    $crear = $this->actingAs($elaborador)->post(
        route('informes-razonados.ejecuciones.excepciones.store', $ejecucion),
        ['codigo' => 'C2', 'descripcion' => 'Fuera de elaboración', 'severidad' => 'info']
    );
    $crear->assertForbidden();

    $editar = $this->actingAs($elaborador)->patch(
        route('informes-razonados.excepciones.update', $excepcion),
        ['descripcion' => 'Editada fuera de elaboración', 'severidad' => 'info']
    );
    $editar->assertForbidden();

    $eliminar = $this->actingAs($elaborador)->delete(
        route('informes-razonados.excepciones.destroy', $excepcion)
    );
    $eliminar->assertForbidden();

    expect($ejecucion->excepciones()->count())->toBe(1);
    expect($excepcion->refresh()->descripcion)->toBe('En elaboración');
});

test('una severidad inválida es rechazada por validación', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $servicio = app(InformeRazonadoService::class);
    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);

    $crear = $this->actingAs($elaborador)->post(
        route('informes-razonados.ejecuciones.excepciones.store', $ejecucion),
        ['codigo' => 'C1', 'descripcion' => 'Sev inválida', 'severidad' => 'urgente']
    );
    $crear->assertSessionHasErrors('severidad');
    expect($ejecucion->excepciones()->count())->toBe(0);

    $excepcion = $servicio->agregarExcepcion($ejecucion, 'C2', 'Válida', 'info');
    $editar = $this->actingAs($elaborador)->patch(
        route('informes-razonados.excepciones.update', $excepcion),
        ['descripcion' => 'Cambiada', 'severidad' => 'mega']
    );
    $editar->assertSessionHasErrors('severidad');
    expect($excepcion->refresh()->severidad)->toBe('info');
});
