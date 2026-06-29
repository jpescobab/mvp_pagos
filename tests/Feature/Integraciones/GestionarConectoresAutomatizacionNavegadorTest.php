<?php

use App\Models\ConectorAutomatizacionNavegador;
use App\Models\PerfilAutenticacionNavegador;
use App\Models\SecurityAuditLog;
use App\Models\SistemaExterno;
use App\Models\User;
use Database\Seeders\IntegracionesSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('un usuario con el permiso integraciones.gestionar_conectores registra un conector inactivo', function () {
    $this->seed(IntegracionesSeeder::class);

    $sistema = SistemaExterno::where('codigo', 'SGF')->first();
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('integraciones.gestionar_conectores');

    $response = $this->actingAs($usuario)->post(route('integraciones.conectores.store'), [
        'sistema_externo_id' => $sistema->id,
        'codigo' => 'SGF-PLAYWRIGHT',
        'nombre' => 'SGF vía Playwright',
    ]);

    $response->assertSessionHasNoErrors();

    $conector = ConectorAutomatizacionNavegador::where('codigo', 'SGF-PLAYWRIGHT')->first();
    expect($conector)->not->toBeNull();
    expect($conector->activo)->toBeFalse();
    expect($conector->estaAutorizado())->toBeFalse();
});

test('un usuario sin el permiso integraciones.gestionar_conectores no puede registrar un conector', function () {
    $this->seed(IntegracionesSeeder::class);

    $sistema = SistemaExterno::where('codigo', 'SGF')->first();
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(route('integraciones.conectores.store'), [
        'sistema_externo_id' => $sistema->id,
        'codigo' => 'SGF-PLAYWRIGHT',
        'nombre' => 'SGF vía Playwright',
    ]);

    $response->assertForbidden();
    expect(ConectorAutomatizacionNavegador::where('codigo', 'SGF-PLAYWRIGHT')->exists())->toBeFalse();
    expect(SecurityAuditLog::where('event', 'acceso_denegado')->exists())->toBeTrue();
});

test('un usuario con el permiso integraciones.gestionar_conectores puede autorizar un conector', function () {
    $this->seed(IntegracionesSeeder::class);

    $sistema = SistemaExterno::where('codigo', 'SGF')->first();
    $conector = ConectorAutomatizacionNavegador::create([
        'sistema_externo_id' => $sistema->id,
        'codigo' => 'SGF-PLAYWRIGHT',
        'nombre' => 'SGF vía Playwright',
    ]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('integraciones.gestionar_conectores');

    $response = $this->actingAs($usuario)->post(route('integraciones.conectores.autorizar', $conector));

    $response->assertSessionHasNoErrors();

    $conector->refresh();
    expect($conector->activo)->toBeTrue();
    expect($conector->autorizado_por)->toBe($usuario->id);
    expect($conector->autorizado_en)->not->toBeNull();
    expect($conector->estaAutorizado())->toBeTrue();
});

test('un usuario sin el permiso integraciones.gestionar_conectores no puede autorizar un conector', function () {
    $this->seed(IntegracionesSeeder::class);

    $sistema = SistemaExterno::where('codigo', 'SGF')->first();
    $conector = ConectorAutomatizacionNavegador::create([
        'sistema_externo_id' => $sistema->id,
        'codigo' => 'SGF-PLAYWRIGHT',
        'nombre' => 'SGF vía Playwright',
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(route('integraciones.conectores.autorizar', $conector));

    $response->assertForbidden();
    expect($conector->refresh()->estaAutorizado())->toBeFalse();
});

test('registrar un perfil de autenticación persiste solo el almacén y la referencia del secreto', function () {
    $this->seed(IntegracionesSeeder::class);

    $sistema = SistemaExterno::where('codigo', 'SGF')->first();
    $conector = ConectorAutomatizacionNavegador::create([
        'sistema_externo_id' => $sistema->id,
        'codigo' => 'SGF-PLAYWRIGHT',
        'nombre' => 'SGF vía Playwright',
    ]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('integraciones.gestionar_conectores');

    $response = $this->actingAs($usuario)->post(route('integraciones.conectores.perfiles.store', $conector), [
        'nombre' => 'Cuenta de servicio SGF',
        'almacen_secreto' => 'vault',
        'referencia_secreto' => 'secret/conectores/sgf',
    ]);

    $response->assertSessionHasNoErrors();

    $perfil = PerfilAutenticacionNavegador::where('conector_automatizacion_navegador_id', $conector->id)->first();
    expect($perfil)->not->toBeNull();
    expect($perfil->almacen_secreto)->toBe('vault');
    expect($perfil->referencia_secreto)->toBe('secret/conectores/sgf');
    expect($perfil->creado_por)->toBe($usuario->id);
});

test('el listado de conectores incluye su sistema externo y sus perfiles de autenticación', function () {
    $this->seed(IntegracionesSeeder::class);

    $sistema = SistemaExterno::where('codigo', 'SGF')->first();
    $conector = ConectorAutomatizacionNavegador::create([
        'sistema_externo_id' => $sistema->id,
        'codigo' => 'SGF-PLAYWRIGHT',
        'nombre' => 'SGF vía Playwright',
    ]);
    PerfilAutenticacionNavegador::create([
        'conector_automatizacion_navegador_id' => $conector->id,
        'nombre' => 'Cuenta de servicio SGF',
        'almacen_secreto' => 'vault',
        'referencia_secreto' => 'secret/conectores/sgf',
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('integraciones.conectores.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('integraciones/conectores/index')
        ->where('conectores.0.sistema_externo.codigo', 'SGF')
        ->has('conectores.0.perfiles', 1)
    );
});
