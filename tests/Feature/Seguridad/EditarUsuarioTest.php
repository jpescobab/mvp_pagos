<?php

use App\Models\AuditLog;
use App\Models\Funcionario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

function datosEdicion(array $overrides = []): array
{
    return array_merge([
        'name' => 'Nombre Editado',
        'email' => 'editado@example.com',
        'rut' => '22.333.444-5',
        'cargo' => 'Jefe de Unidad',
        'unidad' => 'Contabilidad',
        'cfinanciero_id' => null,
        'ccosto_id' => null,
    ], $overrides);
}

test('un usuario con usuarios.editar puede ver el formulario de edición precargado', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.editar');

    $usuario = User::factory()->create(['name' => 'Original', 'email' => 'original@example.com']);
    Funcionario::create([
        'user_id' => $usuario->id,
        'rut' => '11.111.111-1',
        'nombre' => $usuario->name,
        'cargo' => 'Analista',
    ]);

    $response = $this->actingAs($actor)->get(route('usuarios.edit', $usuario));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('seguridad/usuarios/edit')
        ->where('usuario.name', 'Original')
        ->where('usuario.email', 'original@example.com')
        ->where('usuario.rut', '11.111.111-1')
        ->where('usuario.cargo', 'Analista'));
});

test('el formulario de edición funciona para usuarios sin funcionario', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.editar');

    $usuario = User::factory()->create();

    $response = $this->actingAs($actor)->get(route('usuarios.edit', $usuario));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('seguridad/usuarios/edit')
        ->where('usuario.rut', null)
        ->where('usuario.cargo', null));
});

test('un usuario sin usuarios.editar no puede ver el formulario ni actualizar', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $usuario = User::factory()->create(['name' => 'Intocable']);

    $this->actingAs($actor)->get(route('usuarios.edit', $usuario))->assertForbidden();
    $this->actingAs($actor)->patch(route('usuarios.update', $usuario), datosEdicion())->assertForbidden();

    expect($usuario->refresh()->name)->toBe('Intocable');
});

test('la edición actualiza el usuario y su funcionario', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.editar');

    $usuario = User::factory()->create();
    Funcionario::create([
        'user_id' => $usuario->id,
        'rut' => '11.111.111-1',
        'nombre' => $usuario->name,
    ]);

    $response = $this->actingAs($actor)->patch(route('usuarios.update', $usuario), datosEdicion());

    $response->assertRedirect(route('usuarios.index'));

    $usuario->refresh();
    expect($usuario->name)->toBe('Nombre Editado');
    expect($usuario->email)->toBe('editado@example.com');
    expect($usuario->funcionario->rut)->toBe('22.333.444-5');
    expect($usuario->funcionario->nombre)->toBe('Nombre Editado');
    expect($usuario->funcionario->cargo)->toBe('Jefe de Unidad');
    expect($usuario->funcionario->unidad)->toBe('Contabilidad');
});

test('la edición crea el funcionario si el usuario no tenía', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.editar');

    $usuario = User::factory()->create();
    expect($usuario->funcionario)->toBeNull();

    $this->actingAs($actor)->patch(route('usuarios.update', $usuario), datosEdicion());

    $funcionario = Funcionario::where('user_id', $usuario->id)->first();
    expect($funcionario)->not->toBeNull();
    expect($funcionario->rut)->toBe('22.333.444-5');
});

test('la edición acepta conservar el email y rut propios', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.editar');

    $usuario = User::factory()->create(['email' => 'propio@example.com']);
    Funcionario::create([
        'user_id' => $usuario->id,
        'rut' => '11.111.111-1',
        'nombre' => $usuario->name,
    ]);

    $response = $this->actingAs($actor)->patch(route('usuarios.update', $usuario), datosEdicion([
        'email' => 'propio@example.com',
        'rut' => '11.111.111-1',
    ]));

    $response->assertRedirect(route('usuarios.index'));
    $response->assertSessionHasNoErrors();
});

test('la edición rechaza el email de otro usuario y el rut de otro funcionario', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.editar');

    $otro = User::factory()->create(['email' => 'ajeno@example.com']);
    Funcionario::create([
        'user_id' => $otro->id,
        'rut' => '99.999.999-9',
        'nombre' => $otro->name,
    ]);

    $usuario = User::factory()->create(['name' => 'Sin Cambios']);

    $this->actingAs($actor)
        ->patch(route('usuarios.update', $usuario), datosEdicion(['email' => 'ajeno@example.com']))
        ->assertInvalid(['email']);

    $this->actingAs($actor)
        ->patch(route('usuarios.update', $usuario), datosEdicion(['rut' => '99.999.999-9']))
        ->assertInvalid(['rut']);

    expect($usuario->refresh()->name)->toBe('Sin Cambios');
});

test('la edición registra auditoría editar_usuario con before y after', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.editar');

    $usuario = User::factory()->create(['name' => 'Antes']);
    Funcionario::create([
        'user_id' => $usuario->id,
        'rut' => '11.111.111-1',
        'nombre' => $usuario->name,
    ]);

    $this->actingAs($actor)->patch(route('usuarios.update', $usuario), datosEdicion());

    $auditLog = AuditLog::where('action', 'editar_usuario')->where('auditable_id', $usuario->id)->first();
    expect($auditLog)->not->toBeNull();
    expect($auditLog->before['name'])->toBe('Antes');
    expect($auditLog->after['name'])->toBe('Nombre Editado');
    expect($auditLog->user_id)->toBe($actor->id);
});

test('la edición no altera los roles del usuario', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.editar');

    $rol = Role::firstOrCreate(['name' => 'auditor']);
    $usuario = User::factory()->create();
    $usuario->assignRole($rol);

    $this->actingAs($actor)->patch(route('usuarios.update', $usuario), datosEdicion());

    expect($usuario->refresh()->hasRole('auditor'))->toBeTrue();
    expect($usuario->roles)->toHaveCount(1);
});
