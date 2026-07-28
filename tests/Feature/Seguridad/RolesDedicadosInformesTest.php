<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowInformesRazonadosSeeder;
use Spatie\Permission\Models\Role;

test('el seeder crea los tres roles dedicados con su conjunto exacto de permisos', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $esperado = [
        'gestor_reportabilidad' => [
            'informes.ver',
            'reportabilidad.generar_corte',
            'reportabilidad.publicar_corte',
            'reportabilidad.ver',
        ],
        'elaborador_informes' => [
            'informes.elaborar',
            'informes.exportar',
            'informes.ver',
        ],
        'revisor_informes' => [
            'informes.aprobar',
            'informes.exportar',
            'informes.publicar',
            'informes.ver',
        ],
    ];

    foreach ($esperado as $rol => $permisos) {
        $actual = Role::where('name', $rol)
            ->firstOrFail()
            ->permissions
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        expect($actual)->toBe($permisos);
    }
});

test('separación de deberes: el elaborador no puede aprobar ni publicar', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = Role::where('name', 'elaborador_informes')->firstOrFail();

    expect($elaborador->hasPermissionTo('informes.elaborar'))->toBeTrue();
    expect($elaborador->hasPermissionTo('informes.aprobar'))->toBeFalse();
    expect($elaborador->hasPermissionTo('informes.publicar'))->toBeFalse();
});

test('separación de deberes: el revisor no puede elaborar', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $revisor = Role::where('name', 'revisor_informes')->firstOrFail();

    expect($revisor->hasPermissionTo('informes.aprobar'))->toBeTrue();
    expect($revisor->hasPermissionTo('informes.publicar'))->toBeTrue();
    expect($revisor->hasPermissionTo('informes.elaborar'))->toBeFalse();
});

test('el gestor de reportabilidad gobierna los cortes pero no elabora ni aprueba', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $gestor = Role::where('name', 'gestor_reportabilidad')->firstOrFail();

    expect($gestor->hasPermissionTo('reportabilidad.generar_corte'))->toBeTrue();
    expect($gestor->hasPermissionTo('reportabilidad.publicar_corte'))->toBeTrue();
    expect($gestor->hasPermissionTo('informes.elaborar'))->toBeFalse();
    expect($gestor->hasPermissionTo('informes.aprobar'))->toBeFalse();
    expect($gestor->hasPermissionTo('informes.publicar'))->toBeFalse();
});

test('la siembra es idempotente y admin conserva el superset del flujo', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowInformesRazonadosSeeder::class);
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = Role::where('name', 'elaborador_informes')->firstOrFail();
    expect($elaborador->permissions->pluck('name')->sort()->values()->all())->toBe([
        'informes.elaborar',
        'informes.exportar',
        'informes.ver',
    ]);

    $admin = Role::where('name', 'admin')->firstOrFail();
    foreach ([
        'reportabilidad.generar_corte',
        'reportabilidad.publicar_corte',
        'informes.administrar',
        'informes.elaborar',
        'informes.aprobar',
        'informes.publicar',
        'informes.exportar',
    ] as $permiso) {
        expect($admin->hasPermissionTo($permiso))->toBeTrue();
    }
});
