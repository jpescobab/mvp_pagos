<?php

use App\Models\Ccosto;
use App\Models\Funcionario;
use App\Models\IndicadorEconomico;
use App\Models\IndicadorEconomicoImportacion;
use App\Models\Institucion;
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
    $sufijo = fake()->unique()->numerify('####');

    $institucion = Institucion::create(['codigo' => "CAPJ-{$sufijo}", 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => "14-{$sufijo}", 'nombre' => 'Zonal Coyhaique']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => "CF-{$sufijo}", 'nombre' => 'Centro Financiero 1']);

    return $cfinanciero->ccostos()->create(['codigo' => "CC-{$sufijo}", 'nombre' => 'Centro de Costo 1']);
}

function crearFuncionarioDePruebaParaApi(int $ccostoId): Funcionario
{
    return Funcionario::create([
        'rut' => fake()->unique()->numerify('#########'),
        'nombre' => fake()->name(),
        'ccosto_id' => $ccostoId,
        'activo' => true,
    ]);
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
 * @return array<string, mixed>
 */
function datosProcesoAdquisicionParaApi(array $overrides = []): array
{
    $ccostoId = $overrides['ccosto_id'] ?? crearCcostoDePruebaParaApi()->id;

    return array_merge([
        'fecha_inicio' => now()->toDateString(),
        'nombre' => 'Compra de prueba vía API',
        'id_requerimiento' => null,
        'ccosto_id' => $ccostoId,
        'funcionario_requirente_id' => crearFuncionarioDePruebaParaApi($ccostoId)->id,
        'caracteristicas' => 'Características de prueba',
        'motivo_contratacion' => 'Motivo de prueba',
        'en_plan_compras' => false,
        'id_pac' => null,
        'codigo_bip' => null,
        'convenio_marco' => true,
        'moneda_compra' => 'CLP',
        'monto_estimado_solicitado' => 100000,
        'fecha_paridad' => null,
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
        ->where('proceso.nombre', 'Compra de prueba vía API')
        ->where('proceso.proceso.estado_actual.codigo', 'borrador')
        ->where('proceso.proceso.historial_transiciones', [])
        ->where('proceso.proceso.transiciones_disponibles.0.codigo', 'enviar_a_revision')
    );
});

test('procesos.create responde con los ccostos y funcionarios disponibles, sin modalidades', function () {
    $this->withoutVite();
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
    $ccosto = crearCcostoDePruebaParaApi();
    crearFuncionarioDePruebaParaApi($ccosto->id);

    $usuario = usuarioConPermisosAdquisiciones(['adquisiciones.crear_proceso']);

    $response = $this->actingAs($usuario)->get(route('adquisiciones.procesos.create'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('adquisiciones/procesos/crear', shouldExist: false)
        ->where('ccostos.0.codigo', $ccosto->codigo)
        ->where('funcionarios.0.ccosto_id', $ccosto->id)
        ->missing('modalidades')
    );
});

test('crear un proceso de adquisición con datos válidos crea el proceso y su workflow en estado inicial', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
    $ccosto = crearCcostoDePruebaParaApi();
    $funcionario = crearFuncionarioDePruebaParaApi($ccosto->id);

    $usuario = usuarioConPermisosAdquisiciones(['adquisiciones.crear_proceso']);

    $response = $this->actingAs($usuario)->post(route('adquisiciones.procesos.store'), [
        'fecha_inicio' => now()->toDateString(),
        'nombre' => 'Compra de equipos',
        'ccosto_id' => $ccosto->id,
        'funcionario_requirente_id' => $funcionario->id,
        'caracteristicas' => 'Equipos de oficina',
        'motivo_contratacion' => 'Reposición de equipos dados de baja',
        'en_plan_compras' => false,
        'convenio_marco' => true,
        'monto_estimado_solicitado' => 500000,
    ]);

    $response->assertSessionHasNoErrors();

    $proceso = ProcesoAdquisicion::where('nombre', 'Compra de equipos')->first();
    expect($proceso)->not->toBeNull();
    expect($proceso->modalidad->codigo)->toBe('CONVENIO_MARCO');
    expect($proceso->proceso->estadoActual->codigo)->toBe('borrador');
    $response->assertRedirect(route('adquisiciones.procesos.show', $proceso));
});

test('crear con un funcionario requirente que no pertenece a la unidad elegida es rechazado', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
    $ccosto = crearCcostoDePruebaParaApi();
    $otroCcosto = crearCcostoDePruebaParaApi();
    $funcionarioDeOtraUnidad = crearFuncionarioDePruebaParaApi($otroCcosto->id);

    $usuario = usuarioConPermisosAdquisiciones(['adquisiciones.crear_proceso']);

    $response = $this->actingAs($usuario)->post(route('adquisiciones.procesos.store'), [
        'fecha_inicio' => now()->toDateString(),
        'nombre' => 'Compra rechazada',
        'ccosto_id' => $ccosto->id,
        'funcionario_requirente_id' => $funcionarioDeOtraUnidad->id,
        'caracteristicas' => 'Equipos de oficina',
        'motivo_contratacion' => 'Motivo',
        'en_plan_compras' => false,
        'convenio_marco' => true,
        'monto_estimado_solicitado' => 500000,
    ]);

    $response->assertSessionHasErrors('funcionario_requirente_id');
    expect(ProcesoAdquisicion::where('nombre', 'Compra rechazada')->exists())->toBeFalse();
});

test('crear con un monto estimado igual o mayor a 1.000 UTM es rechazado', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
    $ccosto = crearCcostoDePruebaParaApi();
    $funcionario = crearFuncionarioDePruebaParaApi($ccosto->id);

    $importacion = IndicadorEconomicoImportacion::create(['tipo_importacion' => 'mensual_utm', 'estado' => 'success']);
    IndicadorEconomico::create([
        'importacion_id' => $importacion->id,
        'codigo' => 'UTM',
        'nombre' => 'Unidad Tributaria Mensual',
        'tipo' => 'utm',
        'periodo' => now()->format('Y-m'),
        'valor' => 65000,
        'periodicidad_valor' => 'mensual',
        'unidad_medida' => 'CLP',
        'moneda_base' => 'CLP',
        'fuente' => 'SII',
    ]);

    $usuario = usuarioConPermisosAdquisiciones(['adquisiciones.crear_proceso']);

    $response = $this->actingAs($usuario)->post(route('adquisiciones.procesos.store'), [
        'fecha_inicio' => now()->toDateString(),
        'nombre' => 'Compra sobre el umbral',
        'ccosto_id' => $ccosto->id,
        'funcionario_requirente_id' => $funcionario->id,
        'caracteristicas' => 'Equipos de oficina',
        'motivo_contratacion' => 'Motivo',
        'en_plan_compras' => false,
        'convenio_marco' => true,
        'monto_estimado_solicitado' => 65_000_000,
    ]);

    $response->assertSessionHasErrors('monto_estimado_solicitado');
    expect(ProcesoAdquisicion::where('nombre', 'Compra sobre el umbral')->exists())->toBeFalse();
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
    $funcionario = crearFuncionarioDePruebaParaApi($ccosto->id);

    // Solo consulta: puede ver, pero no crear.
    $usuarioSoloConsulta = usuarioConPermisosAdquisiciones(['adquisiciones.consultar_proceso']);

    $this->actingAs($usuarioSoloConsulta)
        ->get(route('adquisiciones.procesos.create'))
        ->assertForbidden();

    $this->actingAs($usuarioSoloConsulta)
        ->post(route('adquisiciones.procesos.store'), [
            'fecha_inicio' => now()->toDateString(),
            'nombre' => 'Compra sin permiso',
            'ccosto_id' => $ccosto->id,
            'funcionario_requirente_id' => $funcionario->id,
            'caracteristicas' => 'Equipos de oficina',
            'motivo_contratacion' => 'Motivo',
            'en_plan_compras' => false,
            'convenio_marco' => true,
            'monto_estimado_solicitado' => 500000,
        ])
        ->assertForbidden();

    expect(ProcesoAdquisicion::where('nombre', 'Compra sin permiso')->exists())->toBeFalse();
});

test('el seeder de workflow otorga los permisos de proceso a admin y a administrativo_adquisiciones', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $admin = Role::where('name', 'admin')->firstOrFail();
    expect($admin->hasPermissionTo('adquisiciones.consultar_proceso'))->toBeTrue();
    expect($admin->hasPermissionTo('adquisiciones.crear_proceso'))->toBeTrue();
    expect($admin->hasPermissionTo('adquisiciones.editar_proceso'))->toBeTrue();

    $administrativo = Role::where('name', 'administrativo_adquisiciones')->firstOrFail();
    expect($administrativo->hasPermissionTo('adquisiciones.consultar_proceso'))->toBeTrue();
    expect($administrativo->hasPermissionTo('adquisiciones.crear_proceso'))->toBeTrue();
    expect($administrativo->hasPermissionTo('adquisiciones.editar_proceso'))->toBeTrue();
    expect($administrativo->hasPermissionTo('adquisiciones.publicar'))->toBeTrue();
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

test('descargar el PDF de la solicitud requiere el permiso de consulta', function () {
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
    $proceso = app(ProcesoAdquisicionService::class)->crear(datosProcesoAdquisicionParaApi());

    $usuarioConPermiso = usuarioConPermisosAdquisiciones(['adquisiciones.consultar_proceso']);
    $this->actingAs($usuarioConPermiso)
        ->get(route('adquisiciones.procesos.pdf', $proceso))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    $usuarioSinPermiso = User::factory()->create();
    $this->actingAs($usuarioSinPermiso)
        ->get(route('adquisiciones.procesos.pdf', $proceso))
        ->assertForbidden();
});
