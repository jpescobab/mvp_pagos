<?php

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('un usuario con usuarios.desactivar puede desactivar a otro usuario activo', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.desactivar');

    $activo = User::factory()->create();

    $response = $this->actingAs($actor)->patch(route('usuarios.desactivar', $activo));

    $response->assertRedirect();
    expect($activo->refresh()->active)->toBeFalse();
    expect(AuditLog::where('action', 'desactivar_usuario')->where('auditable_id', $activo->id)->exists())->toBeTrue();
});

test('un usuario sin usuarios.desactivar no puede desactivar a otro usuario', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $activo = User::factory()->create();

    $response = $this->actingAs($actor)->patch(route('usuarios.desactivar', $activo));

    $response->assertForbidden();
    expect($activo->refresh()->active)->toBeTrue();
});

test('un usuario no puede auto-desactivarse', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.desactivar');

    $response = $this->actingAs($actor)->patch(route('usuarios.desactivar', $actor));

    $response->assertRedirect();
    $response->assertInertiaFlash('error');
    expect($actor->refresh()->active)->toBeTrue();
});

test('no se puede desactivar al último administrador activo', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $superadmin = User::factory()->create();
    $superadmin->assignRole('superadmin');

    $otroActor = User::factory()->create();
    $otroActor->givePermissionTo('usuarios.desactivar');

    $response = $this->actingAs($otroActor)->patch(route('usuarios.desactivar', $superadmin));

    $response->assertRedirect();
    $response->assertInertiaFlash('error');
    expect($superadmin->refresh()->active)->toBeTrue();
});

test('se puede desactivar a un administrador si existe otro administrador activo', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $superadminUno = User::factory()->create();
    $superadminUno->assignRole('superadmin');

    $superadminDos = User::factory()->create();
    $superadminDos->assignRole('superadmin');

    $otroActor = User::factory()->create();
    $otroActor->givePermissionTo('usuarios.desactivar');

    $response = $this->actingAs($otroActor)->patch(route('usuarios.desactivar', $superadminUno));

    $response->assertRedirect();
    expect($superadminUno->refresh()->active)->toBeFalse();
});
