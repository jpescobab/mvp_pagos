<?php

use App\Models\EgresoCgu;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\TrabajoIntegracion;
use App\Models\User;
use Database\Seeders\ModalidadesAdquisicionSeeder;
use Database\Seeders\WorkflowAdquisicionesSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('el formulario de crear egreso excluye los casos que ya tienen un egreso asignado', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $casoLibre = crearCasoPagoProveedorDePrueba('sgf-crear-egreso-libre');
    $casoAsignado = crearCasoPagoProveedorDePrueba('sgf-crear-egreso-asignado');

    $egreso = EgresoCgu::create([
        'numero_egreso' => 'EGR-YA-ASIGNADO',
        'fecha' => now(),
        'monto_total' => $casoAsignado->monto,
    ]);
    $egreso->items()->create([
        'caso_pago_proveedor_id' => $casoAsignado->id,
        'monto' => $casoAsignado->monto,
    ]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.registrar_egreso');

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.egresos-cgu.create'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('pago-proveedores/egresos-cgu/crear', shouldExist: false)
        ->where('casos', fn ($casos) => collect($casos)->pluck('id')->contains($casoLibre->id)
            && ! collect($casos)->pluck('id')->contains($casoAsignado->id))
    );
});

test('crear un egreso CGU con un caso que ya fue asignado a otro egreso es rechazado', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba('sgf-crear-egreso-carrera');

    $egresoExistente = EgresoCgu::create([
        'numero_egreso' => 'EGR-CARRERA-001',
        'fecha' => now(),
        'monto_total' => $caso->monto,
    ]);
    $egresoExistente->items()->create([
        'caso_pago_proveedor_id' => $caso->id,
        'monto' => $caso->monto,
    ]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.registrar_egreso');

    $response = $this->actingAs($usuario)->post(route('pago-proveedores.egresos-cgu.store'), [
        'numero_egreso' => 'EGR-CARRERA-002',
        'fecha' => now()->toDateString(),
        'casos' => [
            ['caso_pago_proveedor_id' => $caso->id, 'monto' => $caso->monto],
        ],
    ]);

    $response->assertSessionHasErrors('casos');
    expect(EgresoCgu::where('numero_egreso', 'EGR-CARRERA-002')->exists())->toBeFalse();
});

test('asignar un caso a un egreso lo avanza a en_revision_finanzas', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba('sgf-crear-egreso-avanza');
    expect($caso->proceso->estadoActual->codigo)->toBe('importada_desde_sgf');

    $usuario = User::factory()->create();
    $usuario->givePermissionTo(['pago_proveedores.registrar_egreso', 'pago_proveedores.gestionar_caso']);

    $response = $this->actingAs($usuario)->post(route('pago-proveedores.egresos-cgu.store'), [
        'numero_egreso' => 'EGR-AVANZA-001',
        'fecha' => now()->toDateString(),
        'casos' => [
            ['caso_pago_proveedor_id' => $caso->id, 'monto' => $caso->monto],
        ],
    ]);

    $response->assertSessionHasNoErrors();
    expect($caso->proceso->refresh()->estadoActual->codigo)->toBe('en_revision_finanzas');
});

test('crear un egreso CGU completa su centro financiero si el caso ya está vinculado a una adquisición', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $proceso = crearProcesoAdquisicionDePrueba();
    $caso = crearCasoPagoProveedorDePrueba('sgf-crear-egreso-cfinanciero');
    $caso->update(['proceso_adquisicion_id' => $proceso->id]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo(['pago_proveedores.registrar_egreso', 'pago_proveedores.gestionar_caso']);

    $this->actingAs($usuario)->post(route('pago-proveedores.egresos-cgu.store'), [
        'numero_egreso' => 'EGR-CFIN-001',
        'fecha' => now()->toDateString(),
        'casos' => [
            ['caso_pago_proveedor_id' => $caso->id, 'monto' => $caso->monto],
        ],
    ]);

    $egreso = EgresoCgu::where('numero_egreso', 'EGR-CFIN-001')->first();
    expect($egreso->cfinanciero_id)->toBe($proceso->ccosto->cfinanciero_id);
});

test('el formulario de crear egreso sin trabajo_integracion_id no cambia su comportamiento por defecto', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba('sgf-sin-param-1');

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.registrar_egreso');

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.egresos-cgu.create'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('pago-proveedores/egresos-cgu/crear', shouldExist: false)
        ->where('trabajoIntegracionId', null)
        ->where('casos', fn ($casos) => collect($casos)->pluck('id')->contains($caso->id))
    );
});

test('el formulario de crear egreso con trabajo_integracion_id devuelve solo los casos de esa corrida', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $sistema = SistemaExterno::firstOrCreate(
        ['codigo' => 'SGF'],
        ['nombre' => 'SGF', 'tipo_integracion' => 'playwright', 'activo' => true],
    );

    $casoDeLaCorrida = crearCasoPagoProveedorDePrueba('sgf-corrida-1');
    $casoDeOtraCorrida = crearCasoPagoProveedorDePrueba('sgf-otra-corrida-1');

    $trabajo = TrabajoIntegracion::create([
        'sistema_externo_id' => $sistema->id,
        'tipo' => 'importar_grupo_pago_operaciones',
        'mecanismo' => 'playwright',
        'iniciado_en' => now(),
        'estado' => 'completado',
    ]);

    SnapshotDatosExterno::create([
        'sistema_externo_id' => $sistema->id,
        'trabajo_integracion_id' => $trabajo->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => 'sgf-corrida-1',
        'payload_crudo' => [],
        'payload_normalizado' => ['rut' => '11111111-1', 'monto' => 500000],
        'hash' => 'hash-corrida-1',
        'capturado_en' => now(),
    ]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.registrar_egreso');

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.egresos-cgu.create', ['trabajo_integracion_id' => $trabajo->id]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('trabajoIntegracionId', $trabajo->id)
        ->where('casos', fn ($casos) => collect($casos)->pluck('id')->contains($casoDeLaCorrida->id)
            && ! collect($casos)->pluck('id')->contains($casoDeOtraCorrida->id))
    );
});

test('crear un egreso CGU con varios casos suma sus montos en monto_total', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $casoUno = crearCasoPagoProveedorDePrueba('sgf-monto-total-1');
    $casoDos = crearCasoPagoProveedorDePrueba('sgf-monto-total-2');

    $usuario = User::factory()->create();
    $usuario->givePermissionTo(['pago_proveedores.registrar_egreso', 'pago_proveedores.gestionar_caso']);

    $this->actingAs($usuario)->post(route('pago-proveedores.egresos-cgu.store'), [
        'numero_egreso' => 'EGR-MONTOTOTAL-001',
        'fecha' => now()->toDateString(),
        'casos' => [
            ['caso_pago_proveedor_id' => $casoUno->id, 'monto' => $casoUno->monto],
            ['caso_pago_proveedor_id' => $casoDos->id, 'monto' => $casoDos->monto],
        ],
    ]);

    $egreso = EgresoCgu::where('numero_egreso', 'EGR-MONTOTOTAL-001')->first();
    expect((float) $egreso->monto_total)->toBe((float) $casoUno->monto + (float) $casoDos->monto);
    expect($egreso->items()->count())->toBe(2);
});

test('crear un egreso CGU con un caso sin vincular deja su centro financiero en null', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba('sgf-crear-egreso-sin-cfinanciero');

    $usuario = User::factory()->create();
    $usuario->givePermissionTo(['pago_proveedores.registrar_egreso', 'pago_proveedores.gestionar_caso']);

    $this->actingAs($usuario)->post(route('pago-proveedores.egresos-cgu.store'), [
        'numero_egreso' => 'EGR-SINCFIN-001',
        'fecha' => now()->toDateString(),
        'casos' => [
            ['caso_pago_proveedor_id' => $caso->id, 'monto' => $caso->monto],
        ],
    ]);

    $egreso = EgresoCgu::where('numero_egreso', 'EGR-SINCFIN-001')->first();
    expect($egreso->cfinanciero_id)->toBeNull();
});
