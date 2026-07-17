<?php

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

test('un usuario con usuarios.asignar_roles puede reasignar los roles de un usuario existente', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.asignar_roles');

    $auditor = Role::firstOrCreate(['name' => 'auditor']);
    $usuario = User::factory()->create();

    $response = $this->actingAs($actor)->patch(route('usuarios.roles.update', $usuario), [
        'roles' => [$auditor->id],
    ]);

    $response->assertRedirect();
    expect($usuario->refresh()->hasRole('auditor'))->toBeTrue();
    expect(AuditLog::where('action', 'reasignar_roles_usuario')->where('auditable_id', $usuario->id)->exists())->toBeTrue();
});

test('no se puede quitar el rol de administrador al último Administrador del Sistema activo', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.asignar_roles');

    $superadmin = User::factory()->create();
    $superadmin->assignRole('superadmin');

    $response = $this->actingAs($actor)->patch(route('usuarios.roles.update', $superadmin), [
        'roles' => [],
    ]);

    $response->assertRedirect();
    $response->assertInertiaFlash('error');
    expect($superadmin->refresh()->hasRole('superadmin'))->toBeTrue();
});

test('se puede quitar el rol de administrador si existe otro administrador activo', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.asignar_roles');

    $superadminUno = User::factory()->create();
    $superadminUno->assignRole('superadmin');

    $superadminDos = User::factory()->create();
    $superadminDos->assignRole('superadmin');

    $response = $this->actingAs($actor)->patch(route('usuarios.roles.update', $superadminUno), [
        'roles' => [],
    ]);

    $response->assertRedirect();
    expect($superadminUno->refresh()->hasRole('superadmin'))->toBeFalse();
});

test('un usuario sin usuarios.asignar_roles no puede reasignar roles', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $auditor = Role::firstOrCreate(['name' => 'auditor']);
    $usuario = User::factory()->create();

    $response = $this->actingAs($actor)->patch(route('usuarios.roles.update', $usuario), [
        'roles' => [$auditor->id],
    ]);

    $response->assertForbidden();
    expect($usuario->refresh()->hasRole('auditor'))->toBeFalse();
});

test('reasignar roles invalida la caché de permisos compartidos del usuario afectado', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.asignar_roles');

    $auditor = Role::firstOrCreate(['name' => 'auditor']);
    $auditor->givePermissionTo('auditoria.ver');

    $usuario = User::factory()->create();

    // Precalienta la caché de permisos compartidos del usuario antes de reasignar roles.
    $antes = $this->actingAs($usuario)->get(route('dashboard'));
    $antes->assertInertia(fn (Assert $page) => $page
        ->where('auth.permissions', fn ($permisos) => ! collect($permisos)->contains('auditoria.ver')));

    $this->actingAs($actor)->patch(route('usuarios.roles.update', $usuario), [
        'roles' => [$auditor->id],
    ]);

    // $usuario ya tiene roles.permissions cargado en memoria desde la
    // request "antes" (Eloquent cachea relaciones por instancia); una
    // request real siempre resuelve un modelo User nuevo, así que se
    // refresca aquí para simular eso y no confundir un artefacto del test
    // con un bug real.
    $despues = $this->actingAs($usuario->fresh())->get(route('dashboard'));
    $despues->assertInertia(fn (Assert $page) => $page
        ->where('auth.permissions', fn ($permisos) => collect($permisos)->contains('auditoria.ver')));
});
