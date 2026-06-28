<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('el seeder crea los roles y permisos esperados', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::pluck('name')->sort()->values()->all())->toBe(['admin', 'superadmin']);
    expect(Permission::pluck('name')->sort()->values()->all())->toBe([
        'core_institucional.administrar',
        'documentos.gestionar',
        'roles.administrar',
        'tablas_maestras.administrar',
        'usuarios.administrar',
    ]);
});

test('admin no tiene el permiso roles.administrar', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = Role::where('name', 'admin')->firstOrFail();

    expect($admin->hasPermissionTo('roles.administrar'))->toBeFalse();
    expect($admin->hasPermissionTo('usuarios.administrar'))->toBeTrue();
});
