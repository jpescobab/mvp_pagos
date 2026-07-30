<?php

use App\Models\Catalogo;
use App\Models\Cfinanciero;
use App\Models\Presupuesto\ImportacionPresupuesto;
use App\Models\Presupuesto\PlanTarea;
use App\Models\Presupuesto\Presupuesto;
use App\Models\SecurityAuditLog;
use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\CfinancierosSeeder;
use Database\Seeders\ItemsSeeder;
use Database\Seeders\JurisdiccionesSeeder;
use Database\Seeders\PresupuestoSeeder;

function usuarioConPermisoConsultar(): User
{
    (new PresupuestoSeeder)->run();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('presupuesto.consultar');

    return $usuario;
}

test('un usuario con presupuesto.consultar ve el listado de líneas de presupuesto', function () {
    $usuario = usuarioConPermisoConsultar();

    $response = $this->actingAs($usuario)->get(route('presupuesto.lineas.index'));

    $response->assertOk();
});

test('el listado de líneas viene ordenado ascendente por número de cuenta y el total general cuadra', function () {
    (new JurisdiccionesSeeder)->run();
    (new CfinancierosSeeder)->run();
    (new ItemsSeeder)->run();
    (new CatalogosSeeder)->run();
    $usuario = usuarioConPermisoConsultar();

    $cfinanciero = Cfinanciero::where('codigo', '1400')->firstOrFail();
    $planTarea = PlanTarea::create(['codigo' => 'PL1', 'nombre' => 'PL1']);

    $catalogoMayor = Catalogo::where('codigo', '2208001001')->firstOrFail();
    $catalogoMenor = Catalogo::where('codigo', '2201001001')->firstOrFail();

    Presupuesto::create([
        'cfinanciero_id' => $cfinanciero->id,
        'catalogo_id' => $catalogoMayor->id,
        'plan_tarea_id' => $planTarea->id,
        'anio' => 2026,
        'monto_asignado' => 100000,
    ]);

    Presupuesto::create([
        'cfinanciero_id' => $cfinanciero->id,
        'catalogo_id' => $catalogoMenor->id,
        'plan_tarea_id' => $planTarea->id,
        'anio' => 2026,
        'monto_asignado' => 250000,
    ]);

    $response = $this->actingAs($usuario)->get(route('presupuesto.lineas.index'));

    $response->assertOk();
    $props = $response->inertiaPage()['props'];

    expect($props['total_asignado'])->toEqual(350000.0);
    expect($props['presupuestos']['data'][0]['catalogo']['codigo'])->toBe('2201001001');
    expect($props['presupuestos']['data'][1]['catalogo']['codigo'])->toBe('2208001001');
});

test('un usuario con presupuesto.consultar ve el historial de importaciones', function () {
    $usuario = usuarioConPermisoConsultar();

    ImportacionPresupuesto::create([
        'nro_version' => '5',
        'anio' => 2026,
        'estado' => 'completado',
        'creado_por_user_id' => $usuario->id,
    ]);

    $response = $this->actingAs($usuario)->get(route('presupuesto.importaciones.index'));

    $response->assertOk();
});

test('un usuario sin presupuesto.consultar no puede ver el listado y queda auditado', function () {
    (new PresupuestoSeeder)->run();
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('presupuesto.lineas.index'));

    $response->assertForbidden();

    expect(SecurityAuditLog::where('event', 'acceso_denegado')->exists())->toBeTrue();
});

test('un usuario sin presupuesto.importar no puede importar y queda auditado', function () {
    (new PresupuestoSeeder)->run();
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(route('presupuesto.importaciones.store'), [
        'anio' => 2026,
    ]);

    $response->assertForbidden();

    expect(SecurityAuditLog::where('event', 'acceso_denegado')->exists())->toBeTrue();
});
