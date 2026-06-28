<?php

use App\Models\AuditLog;
use App\Models\SecurityAuditLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('un usuario con el permiso auditoria.ver puede listar el historial de auditoría', function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);

    AuditLog::create(['action' => 'workflow.transicion', 'before' => ['estado' => 'borrador'], 'after' => ['estado' => 'en_revision']]);
    AuditLog::create(['action' => 'caso_pago_proveedor.vincular_adquisicion']);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('auditoria.ver');

    $response = $this->actingAs($usuario)->get(route('auditoria.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('auditoria/index')
        ->has('registros.data', 2)
        ->where('registros.data.0.action', 'caso_pago_proveedor.vincular_adquisicion')
    );
});

test('un usuario sin el permiso auditoria.ver es bloqueado y queda auditado', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('auditoria.index'));

    $response->assertForbidden();
    expect(SecurityAuditLog::where('event', 'acceso_denegado')->exists())->toBeTrue();
});

test('un usuario no autenticado es redirigido al login', function () {
    $response = $this->get(route('auditoria.index'));

    $response->assertRedirect(route('login'));
});
