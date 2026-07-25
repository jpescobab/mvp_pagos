<?php

use App\Models\Ccosto;
use App\Models\Institucion;
use App\Models\ModalidadAdquisicion;
use App\Models\ProcesoAdquisicion;
use App\Models\User;
use App\Services\Adquisiciones\ProcesoAdquisicionService;
use Database\Seeders\ModalidadesAdquisicionSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowAdquisicionesSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

function crearCcostoDePruebaParaApi(): Ccosto
{
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => '14', 'nombre' => 'Zonal Coyhaique']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => 'CF-001', 'nombre' => 'Centro Financiero 1']);

    return $cfinanciero->ccostos()->create(['codigo' => 'CC-001', 'nombre' => 'Centro de Costo 1']);
}

/**
 * Usuario con acceso a los procesos de adquisición. Requiere que
 * WorkflowAdquisicionesSeeder ya haya sembrado los permisos.
 *
 * @param  list<string>  $permisos
 */
function usuarioConPermisosAdquisiciones(array $permisos = ['adquisiciones.consultar_proceso', 'adquisiciones.crear_proceso']): User
{
    $usuario = User::factory()->create();
    $usuario->givePermissionTo($permisos);

    return $usuario;
}

/**
 * @param  array<string, mixed>  $overrides
 */
function datosProcesoAdquisicionParaApi(array $overrides = []): array
{
    return array_merge([
        'codigo' => 'ADQ-API-'.fake()->unique()->numerify('#####'),
        'modalidad_id' => ModalidadAdquisicion::where('codigo', 'LICITACION_PUBLICA')->value('id'),
        'ccosto_id' => $overrides['ccosto_id'] ?? crearCcostoDePruebaParaApi()->id,
        'objeto' => 'Adquisición de prueba vía API',
    ], $overrides);
}

test('procesos.index responde con la página Inertia incluyendo los procesos', function () {
    $this->withoutVite();
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicionParaApi());

    $usuario = usuarioConPermisosAdquisiciones(['adquisiciones.consultar_proceso']);

    $response = $this->actingAs($usuario)->get(route('adquisiciones.procesos.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('adquisiciones/procesos/index', shouldExist: false)
        ->where('procesos.data.0.codigo', $proceso->codigo)
    );
});

test('procesos.show responde con el proceso, su Proceso, estado actual, historial y transiciones disponibles', function () {
    $this->withoutVite();
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicionParaApi());

    $usuario = usuarioConPermisosAdquisiciones(['adquisiciones.consultar_proceso']);

    $response = $this->actingAs($usuario)->get(route('adquisiciones.procesos.show', $proceso));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('adquisiciones/procesos/show', shouldExist: false)
        ->where('proceso.codigo', $proceso->codigo)
        ->where('proceso.proceso.estado_actual.codigo', 'borrador')
        ->where('proceso.proceso.historial_transiciones', [])
        ->where('proceso.proceso.transiciones_disponibles.0.codigo', 'enviar_a_revision')
    );
});

test('procesos.create responde con las modalidades activas, ccostos y proveedores disponibles', function () {
    $this->withoutVite();
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
    crearCcostoDePruebaParaApi();

    $usuario = usuarioConPermisosAdquisiciones(['adquisiciones.crear_proceso']);

    $response = $this->actingAs($usuario)->get(route('adquisiciones.procesos.create'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('adquisiciones/procesos/crear', shouldExist: false)
        ->where('modalidades.0.codigo', 'LICITACION_PUBLICA')
        ->where('ccostos.0.codigo', 'CC-001')
    );
});

test('crear un proceso de adquisición con datos válidos crea el proceso y su workflow en estado inicial', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
    $ccosto = crearCcostoDePruebaParaApi();
    $modalidadId = ModalidadAdquisicion::where('codigo', 'LICITACION_PUBLICA')->value('id');

    $usuario = usuarioConPermisosAdquisiciones(['adquisiciones.crear_proceso']);

    $response = $this->actingAs($usuario)->post(route('adquisiciones.procesos.store'), [
        'codigo' => 'ADQ-CREADO-001',
        'modalidad_id' => $modalidadId,
        'ccosto_id' => $ccosto->id,
        'objeto' => 'Compra de equipos',
    ]);

    $response->assertSessionHasNoErrors();

    $proceso = ProcesoAdquisicion::where('codigo', 'ADQ-CREADO-001')->first();
    expect($proceso)->not->toBeNull();
    expect($proceso->proceso->estadoActual->codigo)->toBe('borrador');
    $response->assertRedirect(route('adquisiciones.procesos.show', $proceso));
});

test('crear un proceso con una modalidad inexistente o inactiva es rechazado y no crea ningún registro', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
    $ccosto = crearCcostoDePruebaParaApi();
    $modalidadInactiva = ModalidadAdquisicion::create(['codigo' => 'INACTIVA-API', 'nombre' => 'Inactiva', 'activo' => false]);

    $usuario = usuarioConPermisosAdquisiciones(['adquisiciones.crear_proceso']);

    $response = $this->actingAs($usuario)->post(route('adquisiciones.procesos.store'), [
        'codigo' => 'ADQ-RECHAZADO-001',
        'modalidad_id' => $modalidadInactiva->id,
        'ccosto_id' => $ccosto->id,
        'objeto' => 'Compra rechazada',
    ]);

    $response->assertSessionHasErrors('modalidad_id');
    expect(ProcesoAdquisicion::where('codigo', 'ADQ-RECHAZADO-001')->exists())->toBeFalse();
});

test('procesos.index y show se rechazan con 403 sin el permiso adquisiciones.consultar_proceso', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicionParaApi());

    $usuarioSinPermiso = User::factory()->create();

    $this->actingAs($usuarioSinPermiso)
        ->get(route('adquisiciones.procesos.index'))
        ->assertForbidden();

    $this->actingAs($usuarioSinPermiso)
        ->get(route('adquisiciones.procesos.show', $proceso))
        ->assertForbidden();
});

test('procesos.create y store se rechazan con 403 sin el permiso adquisiciones.crear_proceso', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
    $ccosto = crearCcostoDePruebaParaApi();
    $modalidadId = ModalidadAdquisicion::where('codigo', 'LICITACION_PUBLICA')->value('id');

    // Solo consulta: puede ver, pero no crear.
    $usuarioSoloConsulta = usuarioConPermisosAdquisiciones(['adquisiciones.consultar_proceso']);

    $this->actingAs($usuarioSoloConsulta)
        ->get(route('adquisiciones.procesos.create'))
        ->assertForbidden();

    $this->actingAs($usuarioSoloConsulta)
        ->post(route('adquisiciones.procesos.store'), [
            'codigo' => 'ADQ-SIN-PERMISO-001',
            'modalidad_id' => $modalidadId,
            'ccosto_id' => $ccosto->id,
            'objeto' => 'Compra sin permiso',
        ])
        ->assertForbidden();

    expect(ProcesoAdquisicion::where('codigo', 'ADQ-SIN-PERMISO-001')->exists())->toBeFalse();
});

test('el seeder de workflow otorga los permisos de proceso a admin y a administrativo_adquisiciones', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $admin = Role::where('name', 'admin')->firstOrFail();
    expect($admin->hasPermissionTo('adquisiciones.consultar_proceso'))->toBeTrue();
    expect($admin->hasPermissionTo('adquisiciones.crear_proceso'))->toBeTrue();

    $administrativo = Role::where('name', 'administrativo_adquisiciones')->firstOrFail();
    expect($administrativo->hasPermissionTo('adquisiciones.consultar_proceso'))->toBeTrue();
    expect($administrativo->hasPermissionTo('adquisiciones.crear_proceso'))->toBeTrue();
});

test('ejecutar una transición válida con el permiso requerido cambia el estado del Proceso', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicionParaApi());

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(
        route('adquisiciones.procesos.transiciones.store', $proceso),
        ['codigo' => 'enviar_a_revision'],
    );

    $response->assertSessionHasNoErrors();
    expect($proceso->proceso->refresh()->estadoActual->codigo)->toBe('en_revision');
});

test('ejecutar una transición sin el permiso requerido no cambia el estado y refleja el error', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicionParaApi());
    $proceso->proceso->update([
        'estado_actual_id' => $proceso->proceso->definicionWorkflow->estados()->where('codigo', 'en_revision')->value('id'),
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(
        route('adquisiciones.procesos.transiciones.store', $proceso),
        ['codigo' => 'publicar'],
    );

    $response->assertSessionHasErrors('transicion');
    expect($proceso->proceso->refresh()->estadoActual->codigo)->toBe('en_revision');
});
