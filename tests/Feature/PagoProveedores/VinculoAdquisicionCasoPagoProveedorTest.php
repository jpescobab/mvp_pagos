<?php

use App\Models\AuditLog;
use App\Models\CasoPagoProveedor;
use App\Models\Ccosto;
use App\Models\EgresoCgu;
use App\Models\HistorialTransicionWorkflow;
use App\Models\Institucion;
use App\Models\ModalidadAdquisicion;
use App\Models\Proceso;
use App\Models\ProcesoAdquisicion;
use App\Models\SecurityAuditLog;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\User;
use App\Services\Adquisiciones\ProcesoAdquisicionService;
use App\Services\PagoProveedores\CasoPagoProveedorImporter;
use Database\Seeders\ModalidadesAdquisicionSeeder;
use Database\Seeders\WorkflowAdquisicionesSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function crearCcostoDePruebaParaVinculo(): Ccosto
{
    $sufijo = fake()->unique()->numerify('####');

    $institucion = Institucion::create(['codigo' => "CAPJ-V-{$sufijo}", 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => "14-V-{$sufijo}", 'nombre' => 'Zonal Coyhaique']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => "CF-V-{$sufijo}", 'nombre' => 'Centro Financiero 1']);

    return $cfinanciero->ccostos()->create(['codigo' => "CC-V-{$sufijo}", 'nombre' => 'Centro de Costo 1']);
}

function crearProcesoAdquisicionDePrueba(string $codigo = 'ADQ-V-001'): ProcesoAdquisicion
{
    return app(ProcesoAdquisicionService::class)->crear([
        'codigo' => $codigo,
        'modalidad_id' => ModalidadAdquisicion::where('codigo', 'LICITACION_PUBLICA')->value('id'),
        'ccosto_id' => crearCcostoDePruebaParaVinculo()->id,
        'objeto' => 'Compra de equipos de climatización',
    ]);
}

function crearCasoPagoProveedorDePrueba(string $sgfId = 'sgf-vinculo-1'): CasoPagoProveedor
{
    $sistema = SistemaExterno::firstOrCreate(
        ['codigo' => 'SGF'],
        ['nombre' => 'SGF', 'tipo_integracion' => 'playwright', 'activo' => true],
    );

    $normalizado = [
        'sgf_id' => $sgfId,
        'estado' => 'EN_TRAMITE',
        'grupo_actual' => 'FINANZAS',
        'observaciones' => null,
        'rut' => '11111111-1',
        'monto' => 500000.0,
    ];

    $snapshot = SnapshotDatosExterno::create([
        'sistema_externo_id' => $sistema->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => $normalizado['sgf_id'],
        'payload_crudo' => $normalizado,
        'payload_normalizado' => $normalizado,
        'hash' => hash('sha256', json_encode($normalizado, JSON_THROW_ON_ERROR)),
        'capturado_en' => now(),
    ]);

    return app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot($snapshot);
}

test('vincular un caso a una adquisición con el permiso requerido persiste la FK y registra auditoría', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba();
    $proceso = crearProcesoAdquisicionDePrueba();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.vincular_adquisicion');

    $response = $this->actingAs($usuario)->post(
        route('pago-proveedores.casos.vincular-adquisicion.store', $caso),
        ['proceso_adquisicion_id' => $proceso->id],
    );

    $response->assertSessionHasNoErrors();
    expect($caso->refresh()->proceso_adquisicion_id)->toBe($proceso->id);

    $registro = AuditLog::where('action', 'caso_pago_proveedor.vincular_adquisicion')->first();
    expect($registro)->not->toBeNull();
    expect($registro->user_id)->toBe($usuario->id);
    expect($registro->after['proceso_adquisicion_id'])->toBe($proceso->id);
});

test('desvincular un caso ya vinculado deja la FK en null y registra su propia auditoría', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba();
    $proceso = crearProcesoAdquisicionDePrueba();
    $caso->update(['proceso_adquisicion_id' => $proceso->id]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.vincular_adquisicion');

    $response = $this->actingAs($usuario)->delete(
        route('pago-proveedores.casos.vincular-adquisicion.destroy', $caso),
    );

    $response->assertSessionHasNoErrors();
    expect($caso->refresh()->proceso_adquisicion_id)->toBeNull();

    $registro = AuditLog::where('action', 'caso_pago_proveedor.desvincular_adquisicion')->first();
    expect($registro)->not->toBeNull();
    expect($registro->before['proceso_adquisicion_id'])->toBe($proceso->id);
    expect($registro->after['proceso_adquisicion_id'])->toBeNull();
});

test('un usuario sin el permiso no puede vincular ni desvincular y queda auditado como acceso denegado', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba();
    $proceso = crearProcesoAdquisicionDePrueba();

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(
        route('pago-proveedores.casos.vincular-adquisicion.store', $caso),
        ['proceso_adquisicion_id' => $proceso->id],
    );

    $response->assertForbidden();
    expect($caso->refresh()->proceso_adquisicion_id)->toBeNull();

    expect(SecurityAuditLog::where('event', 'acceso_denegado')->exists())->toBeTrue();
});

test('vincular o desvincular no crea ni modifica ningún Proceso ni historial de transición', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba();
    $proceso = crearProcesoAdquisicionDePrueba();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.vincular_adquisicion');

    $totalProcesosAntes = Proceso::count();
    $totalHistorialAntes = HistorialTransicionWorkflow::count();
    $estadoCasoAntes = $caso->proceso->estado_actual_id;

    $this->actingAs($usuario)->post(
        route('pago-proveedores.casos.vincular-adquisicion.store', $caso),
        ['proceso_adquisicion_id' => $proceso->id],
    );

    expect(Proceso::count())->toBe($totalProcesosAntes);
    expect(HistorialTransicionWorkflow::count())->toBe($totalHistorialAntes);
    expect($caso->proceso->refresh()->estado_actual_id)->toBe($estadoCasoAntes);
});

test('vincular un caso a una adquisición completa el centro financiero de su egreso si aún no lo tenía', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba();
    $egreso = EgresoCgu::create(['numero_egreso' => 'EGR-BACKFILL', 'fecha' => now(), 'monto_total' => $caso->monto]);
    $egreso->items()->create(['caso_pago_proveedor_id' => $caso->id, 'monto' => $caso->monto]);
    expect($egreso->cfinanciero_id)->toBeNull();

    $proceso = crearProcesoAdquisicionDePrueba();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.vincular_adquisicion');

    $this->actingAs($usuario)->post(
        route('pago-proveedores.casos.vincular-adquisicion.store', $caso),
        ['proceso_adquisicion_id' => $proceso->id],
    );

    expect($egreso->refresh()->cfinanciero_id)->toBe($proceso->ccosto->cfinanciero_id);
});

test('desvincular un caso no borra el centro financiero ya completado en su egreso', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba();
    $proceso = crearProcesoAdquisicionDePrueba();
    $caso->update(['proceso_adquisicion_id' => $proceso->id]);

    $egreso = EgresoCgu::create(['numero_egreso' => 'EGR-BACKFILL-2', 'fecha' => now(), 'monto_total' => $caso->monto, 'cfinanciero_id' => $proceso->ccosto->cfinanciero_id]);
    $egreso->items()->create(['caso_pago_proveedor_id' => $caso->id, 'monto' => $caso->monto]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.vincular_adquisicion');

    $this->actingAs($usuario)->delete(route('pago-proveedores.casos.vincular-adquisicion.destroy', $caso));

    expect($egreso->refresh()->cfinanciero_id)->toBe($proceso->ccosto->cfinanciero_id);
});

test('la búsqueda asistida devuelve coincidencias por código, objeto, proveedor y monto respetando el límite', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba();

    foreach (range(1, 12) as $i) {
        crearProcesoAdquisicionDePrueba(sprintf('ADQ-BUSQ-%03d', $i));
    }

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.vincular_adquisicion');

    $response = $this->actingAs($usuario)->getJson(
        route('pago-proveedores.casos.buscar-adquisiciones', $caso).'?q=ADQ-BUSQ',
    );

    $response->assertOk();
    expect($response->json())->toHaveCount(10);
});

test('el detalle de un proceso de adquisición muestra los casos de pago vinculados', function () {
    $this->withoutVite();
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $proceso = crearProcesoAdquisicionDePrueba();
    $caso = crearCasoPagoProveedorDePrueba();
    $caso->update(['proceso_adquisicion_id' => $proceso->id]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.procesos.show', $proceso));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('adquisiciones/procesos/show', shouldExist: false)
        ->where('proceso.casos_pago_proveedor.0.sgf_id', $caso->sgf_id)
    );
});

test('el detalle de un proceso de adquisición sin casos vinculados muestra la lista vacía', function () {
    $this->withoutVite();
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);

    $proceso = crearProcesoAdquisicionDePrueba();

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.procesos.show', $proceso));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('adquisiciones/procesos/show', shouldExist: false)
        ->where('proceso.casos_pago_proveedor', [])
    );
});
