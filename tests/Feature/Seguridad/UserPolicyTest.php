<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('delete está prohibido para cualquier usuario sin el bypass de superadmin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $otro = User::factory()->create();

    expect(Gate::forUser($admin)->denies('delete', $otro))->toBeTrue();
});

test('los métodos granulares delegan en su permiso correspondiente', function () {
    $usuario = User::factory()->create();
    $otro = User::factory()->create();

    expect(Gate::forUser($usuario)->denies('viewAny', User::class))->toBeTrue();
    expect(Gate::forUser($usuario)->denies('activar', $otro))->toBeTrue();
    expect(Gate::forUser($usuario)->denies('desactivar', $otro))->toBeTrue();
    expect(Gate::forUser($usuario)->denies('resetearPassword', $otro))->toBeTrue();
    expect(Gate::forUser($usuario)->denies('asignarRoles', $otro))->toBeTrue();

    $usuario->givePermissionTo('usuarios.activar');
    expect(Gate::forUser($usuario)->allows('activar', $otro))->toBeTrue();
});
