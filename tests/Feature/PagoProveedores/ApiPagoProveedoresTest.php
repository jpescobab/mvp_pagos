<?php

use App\Models\EgresoCgu;
use App\Models\EstadoWorkflow;
use App\Models\ImportacionSgf;
use App\Models\SnapshotSgf;
use App\Models\User;
use App\Services\PagoProveedores\CasoPagoProveedorImporter;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @param  array<string, mixed>  $overrides
 */
function crearSnapshotSgfParaApi(array $overrides = []): SnapshotSgf
{
    $importacion = ImportacionSgf::create(['fuente' => 'manual', 'iniciado_en' => now(), 'estado' => 'en_progreso']);

    $normalizado = array_merge([
        'sgf_id' => '90001',
        'estado' => 'EN_TRAMITE',
        'grupo_actual' => 'FINANZAS',
        'observaciones' => null,
        'rut' => '11111111-1',
        'monto' => 500000.0,
    ], $overrides);

    return SnapshotSgf::create([
        'importacion_sgf_id' => $importacion->id,
        'sgf_id' => $normalizado['sgf_id'],
        'payload_crudo' => $normalizado,
        'payload_normalizado' => $normalizado,
        'hash' => hash('sha256', json_encode($normalizado, JSON_THROW_ON_ERROR)),
        'capturado_en' => now(),
    ]);
}

test('casos.index responde con la página Inertia incluyendo los casos', function () {
    $this->withoutVite();
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgfParaApi());

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.casos.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('pago-proveedores/casos/index', shouldExist: false)
        ->where('casos.data.0.sgf_id', $caso->sgf_id)
    );
});

test('casos.show responde con el caso, su Proceso, estado actual, historial y transiciones disponibles', function () {
    $this->withoutVite();
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgfParaApi());

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.casos.show', $caso));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('pago-proveedores/casos/show', shouldExist: false)
        ->where('caso.sgf_id', $caso->sgf_id)
        ->where('caso.proceso.estado_actual.codigo', 'importada_desde_sgf')
        ->where('caso.proceso.historial_transiciones', [])
        ->where('caso.proceso.transiciones_disponibles.0.codigo', 'recibir_en_finanzas')
    );
});

test('ejecutar una transición válida con el permiso requerido cambia el estado del Proceso del caso', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgfParaApi());

    $estadoListaParaRegistro = EstadoWorkflow::where('codigo', 'lista_para_registro_cgu')
        ->where('definicion_workflow_id', $caso->proceso->definicion_workflow_id)
        ->value('id');
    $caso->proceso->update(['estado_actual_id' => $estadoListaParaRegistro]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.registrar_cgu');

    $response = $this->actingAs($usuario)->post(
        route('pago-proveedores.casos.transiciones.store', $caso),
        ['codigo' => 'registrar_en_cgu'],
    );

    $response->assertSessionHasNoErrors();
    expect($caso->proceso->refresh()->estadoActual->codigo)->toBe('registrada_en_cgu');
});

test('ejecutar una transición sin el permiso requerido no cambia el estado y refleja el error', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgfParaApi());

    $estadoListaParaRegistro = EstadoWorkflow::where('codigo', 'lista_para_registro_cgu')
        ->where('definicion_workflow_id', $caso->proceso->definicion_workflow_id)
        ->value('id');
    $caso->proceso->update(['estado_actual_id' => $estadoListaParaRegistro]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(
        route('pago-proveedores.casos.transiciones.store', $caso),
        ['codigo' => 'registrar_en_cgu'],
    );

    $response->assertSessionHasErrors('transicion');
    expect($caso->proceso->refresh()->estadoActual->codigo)->toBe('lista_para_registro_cgu');
});

test('ejecutar un código de transición no válido para el estado actual no cambia el estado del Proceso', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgfParaApi());

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(
        route('pago-proveedores.casos.transiciones.store', $caso),
        ['codigo' => 'cerrar'],
    );

    $response->assertSessionHasErrors('transicion');
    expect($caso->proceso->refresh()->estadoActual->codigo)->toBe('importada_desde_sgf');
});

test('crear un egreso CGU con el permiso requerido crea el egreso y sus items cubriendo varios casos', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $casoUno = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgfParaApi(['sgf_id' => 'caso-api-1']));
    $casoDos = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgfParaApi(['sgf_id' => 'caso-api-2']));

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.registrar_egreso');

    $response = $this->actingAs($usuario)->post(route('pago-proveedores.egresos-cgu.store'), [
        'numero_egreso' => 'EGR-API-001',
        'fecha' => now()->toDateString(),
        'casos' => [
            ['caso_pago_proveedor_id' => $casoUno->id, 'monto' => 300000],
            ['caso_pago_proveedor_id' => $casoDos->id, 'monto' => 200000],
        ],
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('pago-proveedores.egresos-cgu.index'));

    $egreso = EgresoCgu::where('numero_egreso', 'EGR-API-001')->first();
    expect($egreso)->not->toBeNull();
    expect($egreso->items)->toHaveCount(2);
    expect((float) $egreso->monto_total)->toEqual(500000.0);
});

test('crear un egreso CGU sin el permiso requerido es rechazado y no crea ningún registro', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgfParaApi(['sgf_id' => 'caso-api-3']));

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(route('pago-proveedores.egresos-cgu.store'), [
        'numero_egreso' => 'EGR-API-002',
        'fecha' => now()->toDateString(),
        'casos' => [
            ['caso_pago_proveedor_id' => $caso->id, 'monto' => 100000],
        ],
    ]);

    $response->assertForbidden();
    expect(EgresoCgu::where('numero_egreso', 'EGR-API-002')->exists())->toBeFalse();
});
