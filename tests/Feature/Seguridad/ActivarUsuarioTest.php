<?php

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('un usuario con usuarios.activar puede activar a un usuario inactivo', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.activar');

    $inactivo = User::factory()->inactive()->create();

    $response = $this->actingAs($actor)->patch(route('usuarios.activar', $inactivo));

    $response->assertRedirect();
    expect($inactivo->refresh()->active)->toBeTrue();
    expect(AuditLog::where('action', 'activar_usuario')->where('auditable_id', $inactivo->id)->exists())->toBeTrue();
});

test('un usuario sin usuarios.activar no puede activar a otro usuario', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $inactivo = User::factory()->inactive()->create();

    $response = $this->actingAs($actor)->patch(route('usuarios.activar', $inactivo));

    $response->assertForbidden();
    expect($inactivo->refresh()->active)->toBeFalse();
});
