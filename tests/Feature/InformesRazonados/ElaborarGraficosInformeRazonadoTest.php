<?php

use App\Models\GraficoInformeRazonado;
use App\Models\User;
use App\Services\InformesRazonados\InformeRazonadoService;
use Database\Seeders\WorkflowInformesRazonadosSeeder;

// Los helpers corteReportabilidadDePrueba(), definicionInformeRazonadoDePrueba()
// y ejecucionEnElaboracionDePrueba() son globales dentro del directorio.

test('agregar un gráfico con informes.elaborar en una ejecución en elaboración lo crea', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);
    $estadoInicial = $ejecucion->proceso->estadoActual->codigo;

    $response = $this->actingAs($elaborador)->post(
        route('informes-razonados.ejecuciones.graficos.store', $ejecucion),
        [
            'codigo' => 'GRA-1',
            'titulo' => 'Evolución',
            'tipo' => 'barra',
            'datos' => [
                'categorias' => ['Ene', 'Feb', 'Mar'],
                'series' => [['nombre' => 'Total', 'valores' => [1, 2, 3]]],
            ],
            'orden' => 1,
        ]
    );

    $response->assertRedirect();
    expect($ejecucion->graficos()->count())->toBe(1);
    expect($ejecucion->graficos()->first()->tipo)->toBe('barra');
    expect($ejecucion->proceso->refresh()->estadoActual->codigo)->toBe($estadoInicial);
});

test('agregar un gráfico sin informes.elaborar responde 403 y no crea nada', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $sinPermiso = User::factory()->create();
    $ejecucion = ejecucionEnElaboracionDePrueba();

    $response = $this->actingAs($sinPermiso)->post(
        route('informes-razonados.ejecuciones.graficos.store', $ejecucion),
        ['codigo' => 'X', 'titulo' => 'No autorizado', 'tipo' => 'barra', 'datos' => ['a' => 1]]
    );

    $response->assertForbidden();
    expect(GraficoInformeRazonado::count())->toBe(0);
});

test('rechaza un tipo de gráfico inválido por validación', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);

    $response = $this->actingAs($elaborador)->post(
        route('informes-razonados.ejecuciones.graficos.store', $ejecucion),
        ['codigo' => 'GRA-1', 'titulo' => 'Malo', 'tipo' => 'radar', 'datos' => ['a' => 1]]
    );

    $response->assertSessionHasErrors('tipo');
    expect($ejecucion->graficos()->count())->toBe(0);
});

test('rechaza datos de gráfico no estructurados por validación', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);

    $response = $this->actingAs($elaborador)->post(
        route('informes-razonados.ejecuciones.graficos.store', $ejecucion),
        ['codigo' => 'GRA-1', 'titulo' => 'Sin datos', 'tipo' => 'linea', 'datos' => 'no-es-un-array']
    );

    $response->assertSessionHasErrors('datos');
    expect($ejecucion->graficos()->count())->toBe(0);
});

test('rechaza una serie con menos valores que categorías', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);

    $response = $this->actingAs($elaborador)->post(
        route('informes-razonados.ejecuciones.graficos.store', $ejecucion),
        [
            'codigo' => 'GRA-1',
            'titulo' => 'Desalineado',
            'tipo' => 'barra',
            'datos' => [
                'categorias' => ['Ene', 'Feb', 'Mar'],
                'series' => [['nombre' => 'Total', 'valores' => [1, 2]]],
            ],
        ]
    );

    $response->assertSessionHasErrors('datos.series.0.valores');
    expect($ejecucion->graficos()->count())->toBe(0);
});

test('rechaza un gráfico de torta con más de una serie', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);

    $response = $this->actingAs($elaborador)->post(
        route('informes-razonados.ejecuciones.graficos.store', $ejecucion),
        [
            'codigo' => 'GRA-1',
            'titulo' => 'Torta múltiple',
            'tipo' => 'torta',
            'datos' => [
                'categorias' => ['A', 'B'],
                'series' => [
                    ['nombre' => 'Uno', 'valores' => [1, 2]],
                    ['nombre' => 'Dos', 'valores' => [3, 4]],
                ],
            ],
        ]
    );

    $response->assertSessionHasErrors('datos.series');
    expect($ejecucion->graficos()->count())->toBe(0);
});

test('acepta un gráfico de barra con datos canónicos válidos', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);

    $response = $this->actingAs($elaborador)->post(
        route('informes-razonados.ejecuciones.graficos.store', $ejecucion),
        [
            'codigo' => 'GRA-1',
            'titulo' => 'Válido',
            'tipo' => 'barra',
            'datos' => [
                'categorias' => ['Ene', 'Feb'],
                'series' => [
                    ['nombre' => 'Ingresos', 'valores' => [100, 200]],
                    ['nombre' => 'Egresos', 'valores' => [80, 150]],
                ],
            ],
        ]
    );

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
    expect($ejecucion->graficos()->count())->toBe(1);
});

test('editar un gráfico con informes.elaborar actualiza sus campos', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);
    $grafico = app(InformeRazonadoService::class)->agregarGrafico($ejecucion, 'GRA-1', 'Viejo', 'barra', ['a' => 1]);

    $datosNuevos = [
        'categorias' => ['Q1', 'Q2'],
        'series' => [['nombre' => 'Total', 'valores' => [10, 20]]],
    ];

    $response = $this->actingAs($elaborador)->patch(
        route('informes-razonados.graficos.update', $grafico),
        ['titulo' => 'Nuevo', 'tipo' => 'linea', 'datos' => $datosNuevos, 'orden' => 3]
    );

    $response->assertRedirect();
    $grafico->refresh();
    expect($grafico->titulo)->toBe('Nuevo');
    expect($grafico->tipo)->toBe('linea');
    expect($grafico->datos)->toBe($datosNuevos);
});

test('eliminar un gráfico con informes.elaborar lo borra', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);
    $grafico = app(InformeRazonadoService::class)->agregarGrafico($ejecucion, 'GRA-1', 'Descartable', 'torta', ['a' => 1]);

    $response = $this->actingAs($elaborador)->delete(
        route('informes-razonados.graficos.destroy', $grafico)
    );

    $response->assertRedirect();
    expect(GraficoInformeRazonado::whereKey($grafico->id)->exists())->toBeFalse();
});

test('no se pueden elaborar gráficos cuando la ejecución no está en elaboración', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $servicio = app(InformeRazonadoService::class);
    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);
    $grafico = $servicio->agregarGrafico($ejecucion, 'GRA-1', 'En elaboración', 'barra', ['a' => 1]);

    $servicio->enviarARevision($ejecucion, $elaborador);

    $this->actingAs($elaborador)->post(
        route('informes-razonados.ejecuciones.graficos.store', $ejecucion),
        [
            'codigo' => 'GRA-2',
            'titulo' => 'Fuera',
            'tipo' => 'barra',
            'datos' => [
                'categorias' => ['A', 'B'],
                'series' => [['nombre' => 'Total', 'valores' => [1, 2]]],
            ],
        ]
    )->assertForbidden();

    $this->actingAs($elaborador)->delete(
        route('informes-razonados.graficos.destroy', $grafico)
    )->assertForbidden();

    expect($ejecucion->graficos()->count())->toBe(1);
});
