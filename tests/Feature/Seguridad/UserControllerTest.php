<?php

use App\Models\Ccosto;
use App\Models\Cfinanciero;
use App\Models\Funcionario;
use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function crearFuncionario(User $user, array $overrides = []): Funcionario
{
    $institucion = Institucion::create(['codigo' => 'INS-'.uniqid(), 'nombre' => 'Institución de prueba', 'activo' => true]);
    $jurisdiccion = Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => 'JUR-'.uniqid(), 'nombre' => 'Jurisdicción de prueba', 'activo' => true]);
    $cfinanciero = Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => 'CF-'.uniqid(), 'nombre' => 'Centro financiero de prueba', 'activo' => true]);
    $ccosto = Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => 'CC-'.uniqid(), 'nombre' => 'Centro de costo de prueba', 'activo' => true]);

    return Funcionario::create(array_merge([
        'rut' => '11.111.111-1',
        'nombre' => $user->name,
        'user_id' => $user->id,
        'ccosto_id' => $ccosto->id,
        'cfinanciero_id' => $cfinanciero->id,
        'activo' => true,
    ], $overrides));
}

test('un usuario con el permiso usuarios.ver puede listar usuarios', function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('usuarios.ver');

    $response = $this->actingAs($usuario)->get(route('usuarios.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('seguridad/usuarios/index')
        ->has('users.data')
        ->has('filters')
        ->has('catalogs')
        ->has('permissions')
    );
});

test('un usuario sin el permiso usuarios.ver es bloqueado', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('usuarios.index'));

    $response->assertForbidden();
});

test('la búsqueda filtra por nombre, email y rut', function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.ver');

    $porNombre = User::factory()->create(['name' => 'Ana Pérez']);
    $porEmail = User::factory()->create(['name' => 'Zzz', 'email' => 'buscado@example.com']);
    $porRut = User::factory()->create(['name' => 'Yyy']);
    crearFuncionario($porRut, ['rut' => '22.222.222-2']);
    User::factory()->create(['name' => 'No coincide', 'email' => 'nada@example.com']);

    $response = $this->actingAs($actor)->get(route('usuarios.index', ['search' => 'Ana']));
    $response->assertInertia(fn (Assert $page) => $page->where('users.data.0.id', $porNombre->id)->has('users.data', 1));

    $response = $this->actingAs($actor)->get(route('usuarios.index', ['search' => 'buscado@example.com']));
    $response->assertInertia(fn (Assert $page) => $page->where('users.data.0.id', $porEmail->id)->has('users.data', 1));

    $response = $this->actingAs($actor)->get(route('usuarios.index', ['search' => '22.222.222-2']));
    $response->assertInertia(fn (Assert $page) => $page->where('users.data.0.id', $porRut->id)->has('users.data', 1));
});

test('los filtros institucionales acotan el listado', function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.ver');

    $activo = User::factory()->create();
    $inactivo = User::factory()->inactive()->create();

    $response = $this->actingAs($actor)->get(route('usuarios.index', ['estado' => 'inactivo']));
    $response->assertInertia(fn (Assert $page) => $page->where('users.data.0.id', $inactivo->id)->has('users.data', 1));

    $response = $this->actingAs($actor)->get(route('usuarios.index', ['estado' => 'activo']));
    $response->assertInertia(fn (Assert $page) => $page->has('users.data', 2));

    $conFuncionario = User::factory()->create();
    $funcionario = crearFuncionario($conFuncionario);

    $response = $this->actingAs($actor)->get(route('usuarios.index', ['centro_costo_id' => $funcionario->ccosto_id]));
    $response->assertInertia(fn (Assert $page) => $page->where('users.data.0.id', $conFuncionario->id)->has('users.data', 1));

    $response = $this->actingAs($actor)->get(route('usuarios.index', ['centro_financiero_id' => $funcionario->cfinanciero_id]));
    $response->assertInertia(fn (Assert $page) => $page->where('users.data.0.id', $conFuncionario->id)->has('users.data', 1));

    $response = $this->actingAs($actor)->get(route('usuarios.index', ['jurisdiccion_id' => $funcionario->cfinanciero->jurisdiccion_id]));
    $response->assertInertia(fn (Assert $page) => $page->where('users.data.0.id', $conFuncionario->id)->has('users.data', 1));

    $conRol = User::factory()->create();
    $conRol->assignRole('admin');
    $rolId = $conRol->roles->firstOrFail()->id;

    $response = $this->actingAs($actor)->get(route('usuarios.index', ['rol_id' => $rolId]));
    $response->assertInertia(fn (Assert $page) => $page->where('users.data.0.id', $conRol->id)->has('users.data', 1));
});

test('la paginación respeta el tamaño solicitado', function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.ver');
    User::factory()->count(30)->create();

    $response = $this->actingAs($actor)->get(route('usuarios.index', ['per_page' => 25]));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('users.data', 25)
        ->where('filters.per_page', 25)
    );
});

test('el orden inicial muestra usuarios activos primero y luego por nombre', function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.ver');

    $inactivoB = User::factory()->inactive()->create(['name' => 'B Inactivo']);
    $activoZ = User::factory()->create(['name' => 'Z Activo']);
    $activoA = User::factory()->create(['name' => 'A Activo']);

    $response = $this->actingAs($actor)->get(route('usuarios.index', ['per_page' => 100]));

    $ids = collect($response->inertiaProps('users.data'))->pluck('id')->values()->all();

    expect(array_search($activoA->id, $ids, true))->toBeLessThan(array_search($activoZ->id, $ids, true));
    expect(array_search($activoZ->id, $ids, true))->toBeLessThan(array_search($inactivoB->id, $ids, true));
});
