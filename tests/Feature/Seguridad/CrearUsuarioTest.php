<?php

use App\Models\AuditLog;
use App\Models\Funcionario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

function datosUsuarioNuevo(array $overrides = []): array
{
    return array_merge([
        'name' => 'Ana Soto',
        'email' => 'ana.soto@example.com',
        'rut' => '11111111-1',
        'cargo' => 'Analista',
        'unidad' => 'Finanzas',
        'roles' => [],
        'cfinanciero_id' => null,
        'ccosto_id' => null,
    ], $overrides);
}

test('un usuario con usuarios.crear puede ver el formulario de alta', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.crear');

    $response = $this->actingAs($actor)->get(route('usuarios.create'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('seguridad/usuarios/create'));
});

test('un usuario sin usuarios.crear no puede ver el formulario de alta', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->get(route('usuarios.create'));

    $response->assertForbidden();
});

test('un usuario con usuarios.crear puede crear un usuario institucional', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.crear');

    $rol = Role::firstOrCreate(['name' => 'auditor']);

    $response = $this->actingAs($actor)->post(route('usuarios.store'), datosUsuarioNuevo([
        'roles' => [$rol->id],
    ]));

    $response->assertRedirect(route('usuarios.index'));
    $response->assertInertiaFlash('passwordTemporal');

    $usuario = User::where('email', 'ana.soto@example.com')->firstOrFail();
    expect($usuario->active)->toBeTrue();
    expect($usuario->must_change_password)->toBeTrue();
    expect($usuario->hasRole('auditor'))->toBeTrue();

    $funcionario = Funcionario::where('user_id', $usuario->id)->firstOrFail();
    expect($funcionario->rut)->toBe('11111111-1');
    expect($funcionario->cargo)->toBe('Analista');

    expect(AuditLog::where('action', 'crear_usuario')->where('auditable_id', $usuario->id)->exists())->toBeTrue();
});

test('un usuario sin usuarios.crear no puede crear un usuario institucional', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->post(route('usuarios.store'), datosUsuarioNuevo());

    $response->assertForbidden();
    expect(User::where('email', 'ana.soto@example.com')->exists())->toBeFalse();
});

test('rechaza el alta con un email ya utilizado', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.crear');

    $existente = User::factory()->create(['email' => 'ana.soto@example.com']);

    $response = $this->actingAs($actor)->post(route('usuarios.store'), datosUsuarioNuevo());

    $response->assertInvalid(['email']);
    expect(Funcionario::where('rut', '11111111-1')->exists())->toBeFalse();
});

test('rechaza el alta con un rut ya utilizado', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.crear');

    $otroUsuario = User::factory()->create();
    Funcionario::create([
        'user_id' => $otroUsuario->id,
        'rut' => '11111111-1',
        'nombre' => $otroUsuario->name,
    ]);

    $response = $this->actingAs($actor)->post(route('usuarios.store'), datosUsuarioNuevo());

    $response->assertInvalid(['rut']);
    expect(User::where('email', 'ana.soto@example.com')->exists())->toBeFalse();
});
