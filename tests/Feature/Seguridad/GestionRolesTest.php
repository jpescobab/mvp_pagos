<?php

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('un usuario con roles.administrar puede listar roles con sus conteos', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('roles.administrar');

    $rol = Role::firstOrCreate(['name' => 'auditor']);
    $rol->givePermissionTo('auditoria.ver');
    User::factory()->create()->assignRole('auditor');

    $response = $this->actingAs($actor)->get(route('roles.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('seguridad/roles/index')
        ->where('roles', fn ($roles) => collect($roles)->firstWhere('name', 'auditor')['users_count'] === 1
            && collect($roles)->firstWhere('name', 'auditor')['permissions_count'] === 1));
});

test('un usuario sin roles.administrar no puede listar roles', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->get(route('roles.index'));

    $response->assertForbidden();
});

test('un usuario con roles.administrar puede crear un rol con permisos', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('roles.administrar');

    $permiso = Permission::where('name', 'auditoria.ver')->firstOrFail();

    $response = $this->actingAs($actor)->post(route('roles.store'), [
        'name' => 'auditor_externo',
        'permissions' => [$permiso->id],
    ]);

    $response->assertRedirect(route('roles.index'));

    $rol = Role::where('name', 'auditor_externo')->firstOrFail();
    expect($rol->hasPermissionTo('auditoria.ver'))->toBeTrue();
    expect(AuditLog::where('action', 'crear_rol')->where('auditable_id', $rol->id)->exists())->toBeTrue();
});

test('un usuario sin roles.administrar no puede crear un rol', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->post(route('roles.store'), [
        'name' => 'auditor_externo',
        'permissions' => [],
    ]);

    $response->assertForbidden();
    expect(Role::where('name', 'auditor_externo')->exists())->toBeFalse();
});

test('un usuario con roles.administrar puede editar los permisos de un rol', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('roles.administrar');

    $rol = Role::firstOrCreate(['name' => 'auditor']);
    $permisoInicial = Permission::where('name', 'auditoria.ver')->firstOrFail();
    $rol->givePermissionTo($permisoInicial);

    $permisoNuevo = Permission::where('name', 'usuarios.ver')->firstOrFail();

    $response = $this->actingAs($actor)->patch(route('roles.update', $rol), [
        'name' => 'auditor',
        'permissions' => [$permisoNuevo->id],
    ]);

    $response->assertRedirect(route('roles.index'));

    $rol->refresh();
    expect($rol->hasPermissionTo('usuarios.ver'))->toBeTrue();
    expect($rol->hasPermissionTo('auditoria.ver'))->toBeFalse();
    expect(AuditLog::where('action', 'editar_rol')->where('auditable_id', $rol->id)->exists())->toBeTrue();
});

test('editar los permisos de un rol invalida la caché de permisos compartidos de sus usuarios asignados', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('roles.administrar');

    $rol = Role::firstOrCreate(['name' => 'auditor']);
    $permisoInicial = Permission::where('name', 'auditoria.ver')->firstOrFail();
    $rol->givePermissionTo($permisoInicial);

    $usuario = User::factory()->create();
    $usuario->assignRole('auditor');

    // Precalienta la caché de permisos compartidos del usuario con el rol antes de editar.
    $antes = $this->actingAs($usuario)->get(route('dashboard'));
    $antes->assertInertia(fn (Assert $page) => $page
        ->where('auth.permissions', fn ($permisos) => collect($permisos)->contains('auditoria.ver')
            && ! collect($permisos)->contains('usuarios.ver')));

    $permisoNuevo = Permission::where('name', 'usuarios.ver')->firstOrFail();

    $this->actingAs($actor)->patch(route('roles.update', $rol), [
        'name' => 'auditor',
        'permissions' => [$permisoNuevo->id],
    ]);

    // $usuario ya tiene roles.permissions cargado en memoria desde la
    // request "antes" (Eloquent cachea relaciones por instancia); una
    // request real siempre resuelve un modelo User nuevo, así que se
    // refresca aquí para simular eso y no confundir un artefacto del test
    // con un bug real.
    $despues = $this->actingAs($usuario->fresh())->get(route('dashboard'));
    $despues->assertInertia(fn (Assert $page) => $page
        ->where('auth.permissions', fn ($permisos) => ! collect($permisos)->contains('auditoria.ver')
            && collect($permisos)->contains('usuarios.ver')));
});

test('un usuario sin roles.administrar no puede editar un rol', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $rol = Role::firstOrCreate(['name' => 'auditor']);

    $response = $this->actingAs($actor)->patch(route('roles.update', $rol), [
        'name' => 'auditor',
        'permissions' => [],
    ]);

    $response->assertForbidden();
});

test('se puede eliminar un rol sin usuarios asignados', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('roles.administrar');

    $rol = Role::firstOrCreate(['name' => 'auditor']);

    $response = $this->actingAs($actor)->delete(route('roles.destroy', $rol));

    $response->assertRedirect();
    expect(Role::where('name', 'auditor')->exists())->toBeFalse();
    expect(AuditLog::where('action', 'eliminar_rol')->exists())->toBeTrue();
});

test('no se puede eliminar un rol con usuarios asignados', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('roles.administrar');

    $rol = Role::firstOrCreate(['name' => 'auditor']);
    User::factory()->create()->assignRole('auditor');

    $response = $this->actingAs($actor)->delete(route('roles.destroy', $rol));

    $response->assertRedirect();
    $response->assertInertiaFlash('error');
    expect(Role::where('name', 'auditor')->exists())->toBeTrue();
});

test('no se puede eliminar el rol core superadmin', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('roles.administrar');

    $superadmin = Role::where('name', 'superadmin')->firstOrFail();

    $response = $this->actingAs($actor)->delete(route('roles.destroy', $superadmin));

    $response->assertRedirect();
    $response->assertInertiaFlash('error');
    expect(Role::where('name', 'superadmin')->exists())->toBeTrue();
});

test('no se puede eliminar el rol core admin', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('roles.administrar');

    $admin = Role::where('name', 'admin')->firstOrFail();

    $response = $this->actingAs($actor)->delete(route('roles.destroy', $admin));

    $response->assertRedirect();
    $response->assertInertiaFlash('error');
    expect(Role::where('name', 'admin')->exists())->toBeTrue();
});

test('un usuario sin roles.administrar no puede eliminar un rol', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $rol = Role::firstOrCreate(['name' => 'auditor']);

    $response = $this->actingAs($actor)->delete(route('roles.destroy', $rol));

    $response->assertForbidden();
    expect(Role::where('name', 'auditor')->exists())->toBeTrue();
});
