<?php

use App\Models\Presupuesto\ImportacionPresupuesto;
use App\Models\SecurityAuditLog;
use App\Models\User;
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
