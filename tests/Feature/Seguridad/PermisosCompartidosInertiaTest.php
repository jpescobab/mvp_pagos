<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowPagoProveedoresSeeder::class);
});

test('el superadmin recibe todos los permisos (incluidos los de módulos) en auth.permissions', function () {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('superadmin');

    $response = $this->actingAs($superadmin)->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('auth.permissions', fn ($permisos) => collect($permisos)->contains('pago_proveedores.revisar_finanzas')
            && collect($permisos)->contains('pago_proveedores.revisar_zonal'))
    );
});

test('un usuario no superadmin solo recibe los permisos de sus roles', function () {
    $jefe = User::factory()->create();
    $jefe->assignRole('jefe_finanzas');

    $response = $this->actingAs($jefe)->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('auth.permissions', fn ($permisos) => collect($permisos)->contains('pago_proveedores.revisar_finanzas')
            && ! collect($permisos)->contains('pago_proveedores.revisar_zonal'))
    );
});
