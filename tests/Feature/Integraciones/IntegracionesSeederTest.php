<?php

use App\Models\SistemaExterno;
use Database\Seeders\IntegracionesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

test('IntegracionesSeeder crea el catálogo de sistemas_externos y los permisos otorgados al rol admin', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(IntegracionesSeeder::class);

    expect(SistemaExterno::count())->toBe(6);
    expect(SistemaExterno::where('codigo', 'SGF')->first()?->activo)->toBeTrue();
    expect(SistemaExterno::where('codigo', 'CGU')->first()?->activo)->toBeFalse();
    expect(SistemaExterno::where('codigo', 'BANCOESTADO')->first()?->activo)->toBeFalse();
    expect(SistemaExterno::where('codigo', 'SII')->first()?->activo)->toBeFalse();
    expect(SistemaExterno::where('codigo', 'CMF')->first()?->activo)->toBeFalse();
    expect(SistemaExterno::where('codigo', 'MERCADO_PUBLICO')->first()?->activo)->toBeFalse();

    $admin = Role::where('name', 'admin')->first();
    expect($admin?->hasPermissionTo('integraciones.gestionar_conectores'))->toBeTrue();
    expect($admin?->hasPermissionTo('integraciones.ejecutar_playwright'))->toBeTrue();
});
