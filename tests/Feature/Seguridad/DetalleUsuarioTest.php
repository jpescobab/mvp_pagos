<?php

use App\Models\AuditLog;
use App\Models\Ccosto;
use App\Models\Cfinanciero;
use App\Models\Funcionario;
use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\SecurityAuditLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Helper local (no reutiliza el de UserControllerTest a propósito: las funciones
 * de Pest son globales pero solo se cargan si Pest incluye ese archivo, y este
 * test debe poder correrse aislado).
 */
function crearFuncionarioConJerarquia(User $user, array $overrides = []): Funcionario
{
    $institucion = Institucion::create(['codigo' => 'INS-'.uniqid(), 'nombre' => 'Institución de prueba', 'activo' => true]);
    $jurisdiccion = Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => 'JUR-'.uniqid(), 'nombre' => 'Jurisdicción Austral', 'activo' => true]);
    $cfinanciero = Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => 'CF-'.uniqid(), 'nombre' => 'CAPJ Zonal Coyhaique', 'activo' => true]);
    $ccosto = Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => 'CC-'.uniqid(), 'nombre' => 'Administración Zonal', 'activo' => true]);

    return Funcionario::create(array_merge([
        'user_id' => $user->id,
        'rut' => '11.111.111-1',
        'nombre' => $user->name,
        'cargo' => 'Administrativo de Finanzas',
        'unidad' => 'Unidad de Finanzas',
        'cfinanciero_id' => $cfinanciero->id,
        'ccosto_id' => $ccosto->id,
        'activo' => true,
    ], $overrides));
}

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('un usuario con el permiso usuarios.ver recibe el detalle con identidad y ámbito institucional', function () {
    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.ver');

    $objetivo = User::factory()->create(['name' => 'Ana Pérez', 'email' => 'ana.perez@example.com']);
    crearFuncionarioConJerarquia($objetivo);

    $response = $this->actingAs($actor)->get(route('usuarios.show', $objetivo));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('seguridad/usuarios/show')
        ->where('usuario.id', $objetivo->id)
        ->where('usuario.name', 'Ana Pérez')
        ->where('usuario.email', 'ana.perez@example.com')
        ->where('usuario.rut', '11.111.111-1')
        ->where('usuario.cargo', 'Administrativo de Finanzas')
        ->where('usuario.unidad', 'Unidad de Finanzas')
        ->where('usuario.active', true)
        ->has('usuario.last_login_at')
        ->has('usuario.created_at')
        ->where('usuario.jurisdiccion.nombre', 'Jurisdicción Austral')
        ->where('usuario.centro_financiero.nombre', 'CAPJ Zonal Coyhaique')
        ->where('usuario.centro_costo.nombre', 'Administración Zonal')
        ->has('permisos_efectivos')
        ->has('actividad.negocio')
        ->has('actividad.seguridad')
        ->has('permissions')
    );
});

test('un usuario sin el permiso usuarios.ver es bloqueado y queda auditado', function () {
    $actor = User::factory()->create();
    $objetivo = User::factory()->create();

    $response = $this->actingAs($actor)->get(route('usuarios.show', $objetivo));

    $response->assertForbidden();

    $log = SecurityAuditLog::where('event', 'acceso_denegado')->latest('id')->first();

    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($actor->id);
    expect($log->metadata['ability'])->toBe('view');
});

test('un usuario sin funcionario asociado responde 200 con los campos institucionales en null', function () {
    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.ver');

    $objetivo = User::factory()->create();

    $response = $this->actingAs($actor)->get(route('usuarios.show', $objetivo));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('usuario.rut', null)
        ->where('usuario.cargo', null)
        ->where('usuario.unidad', null)
        ->where('usuario.jurisdiccion', null)
        ->where('usuario.centro_financiero', null)
        ->where('usuario.centro_costo', null)
    );
});

test('el detalle de un superadmin reporta acceso total sin enumerar permisos', function () {
    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.ver');

    $objetivo = User::factory()->create();
    $objetivo->assignRole('superadmin');

    $response = $this->actingAs($actor)->get(route('usuarios.show', $objetivo));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('permisos_efectivos.acceso_total', true)
        ->where('permisos_efectivos.permisos', [])
    );
});

test('el detalle de un usuario con rol concreto lista sus permisos efectivos', function () {
    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.ver');

    $objetivo = User::factory()->create();
    $objetivo->assignRole('admin');

    $response = $this->actingAs($actor)->get(route('usuarios.show', $objetivo));

    $permisos = $response->inertiaProps('permisos_efectivos');

    expect($permisos['acceso_total'])->toBeFalse();
    expect($permisos['permisos'])->toContain('usuarios.ver');
    expect($permisos['permisos'])->toBe(collect($permisos['permisos'])->sort()->values()->all());
});

test('un usuario sin roles muestra roles vacíos y sin permisos efectivos', function () {
    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.ver');

    $objetivo = User::factory()->create();

    $response = $this->actingAs($actor)->get(route('usuarios.show', $objetivo));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('usuario.roles', [])
        ->where('permisos_efectivos.acceso_total', false)
        ->where('permisos_efectivos.permisos', [])
    );
});

test('la actividad reciente muestra solo los registros del usuario consultado', function () {
    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.ver');

    $objetivo = User::factory()->create();
    $otro = User::factory()->create();

    AuditLog::create(['user_id' => $objetivo->id, 'action' => 'accion_propia', 'auditable_type' => User::class, 'auditable_id' => $objetivo->id]);
    AuditLog::create(['user_id' => $otro->id, 'action' => 'accion_ajena', 'auditable_type' => User::class, 'auditable_id' => $otro->id]);

    SecurityAuditLog::create(['user_id' => $objetivo->id, 'event' => 'evento_propio', 'description' => 'Propio', 'ip_address' => '10.0.0.1']);
    SecurityAuditLog::create(['user_id' => $otro->id, 'event' => 'evento_ajeno', 'description' => 'Ajeno', 'ip_address' => '10.0.0.2']);

    $response = $this->actingAs($actor)->get(route('usuarios.show', $objetivo));

    $negocio = $response->inertiaProps('actividad.negocio');
    $seguridad = $response->inertiaProps('actividad.seguridad');

    expect($negocio)->toHaveCount(1);
    expect($negocio[0]['action'])->toBe('accion_propia');
    expect($seguridad)->toHaveCount(1);
    expect($seguridad[0]['event'])->toBe('evento_propio');
    expect($seguridad[0]['ip_address'])->toBe('10.0.0.1');
});

test('un usuario sin actividad devuelve ambas colecciones vacías', function () {
    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.ver');

    $objetivo = User::factory()->create();

    $response = $this->actingAs($actor)->get(route('usuarios.show', $objetivo));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('actividad.negocio', 0)
        ->has('actividad.seguridad', 0)
    );
});

test('ver el detalle no escribe en las tablas de auditoría', function () {
    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.ver');

    $objetivo = User::factory()->create();

    $auditLogsAntes = AuditLog::count();
    $securityLogsAntes = SecurityAuditLog::count();

    $this->actingAs($actor)->get(route('usuarios.show', $objetivo))->assertOk();

    expect(AuditLog::count())->toBe($auditLogsAntes);
    expect(SecurityAuditLog::count())->toBe($securityLogsAntes);
});

test('las acciones de cuenta se exponen según el permiso del usuario autenticado', function () {
    $actor = User::factory()->create();
    $actor->givePermissionTo(['usuarios.ver', 'usuarios.editar']);

    $objetivo = User::factory()->create();

    $response = $this->actingAs($actor)->get(route('usuarios.show', $objetivo));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('permissions.can_edit_user', true)
        ->where('permissions.can_reset_password', false)
        ->where('permissions.can_activate_user', false)
        ->where('permissions.can_deactivate_user', false)
    );
});

test('la ruta de creación sigue resolviéndose pese a la ruta de detalle con parámetro', function () {
    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.crear');

    $response = $this->actingAs($actor)->get(route('usuarios.create'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('seguridad/usuarios/create'));
});
