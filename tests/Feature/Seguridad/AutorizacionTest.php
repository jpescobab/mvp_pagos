<?php

use App\Models\SecurityAuditLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('un usuario con rol superadmin pasa cualquier autorización sin permiso asignado', function () {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('superadmin');

    expect(Gate::forUser($superadmin)->allows('una-habilidad-inventada-que-no-existe'))->toBeTrue();
});

test('un usuario sin permiso es denegado por UserPolicy y queda auditado', function () {
    $user = User::factory()->create();

    expect(SecurityAuditLog::count())->toBe(0);

    $denied = Gate::forUser($user)->denies('create', User::class);

    expect($denied)->toBeTrue();
    expect(SecurityAuditLog::count())->toBe(1);

    $log = SecurityAuditLog::first();
    expect($log->event)->toBe('acceso_denegado');
    expect($log->user_id)->toBe($user->id);
    expect($log->metadata['ability'])->toBe('create');
});

test('un admin sin roles.administrar es denegado por RolePolicy', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect(Gate::forUser($admin)->denies('create', Role::class))->toBeTrue();
});
