<?php

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('un usuario con usuarios.resetear_password puede resetear la contraseña de otro usuario', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('usuarios.resetear_password');

    $usuario = User::factory()->create();
    $passwordOriginal = $usuario->password;

    $response = $this->actingAs($actor)->post(route('usuarios.reset-password', $usuario));

    $response->assertRedirect();
    $response->assertInertiaFlash('passwordTemporal');

    $usuario->refresh();
    expect($usuario->password)->not->toBe($passwordOriginal);
    expect($usuario->must_change_password)->toBeTrue();
    expect(AuditLog::where('action', 'resetear_password_usuario')->where('auditable_id', $usuario->id)->exists())->toBeTrue();

    $auditLog = AuditLog::where('action', 'resetear_password_usuario')->where('auditable_id', $usuario->id)->first();
    expect(json_encode($auditLog->after))->not->toContain($usuario->password);
});

test('un usuario sin usuarios.resetear_password no puede resetear la contraseña de otro usuario', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $usuario = User::factory()->create();
    $passwordOriginal = $usuario->password;

    $response = $this->actingAs($actor)->post(route('usuarios.reset-password', $usuario));

    $response->assertForbidden();
    expect($usuario->refresh()->password)->toBe($passwordOriginal);
});
