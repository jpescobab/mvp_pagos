<?php

use App\Exceptions\CorteReportabilidadException;
use App\Models\User;
use App\Services\Reportabilidad\CorteReportabilidadService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowInformesRazonadosSeeder;

test('CorteReportabilidadService abre un período, crea un corte en borrador y le agrega items y snapshots', function () {
    $servicio = app(CorteReportabilidadService::class);
    $usuario = User::factory()->create();

    $periodo = $servicio->abrirPeriodo('2026-06', '2026-06-01', '2026-06-30');
    $corte = $servicio->crearCorte($periodo);

    expect($corte->estado)->toBe('borrador');
    expect($corte->periodo_reportabilidad_id)->toBe($periodo->id);

    $item = $servicio->agregarItem($corte, $usuario, 'usuarios');
    $snapshot = $servicio->capturarSnapshot($corte, ['ejemplo' => true], $item);

    expect($corte->items)->toHaveCount(1);
    expect($corte->snapshots)->toHaveCount(1);
    expect($snapshot->hash)->toBe(hash('sha256', json_encode(['ejemplo' => true], JSON_THROW_ON_ERROR)));
});

test('publicarCorte exige el permiso reportabilidad.publicar_corte', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $servicio = app(CorteReportabilidadService::class);
    $periodo = $servicio->abrirPeriodo('2026-07', '2026-07-01', '2026-07-31');
    $corte = $servicio->crearCorte($periodo);

    $usuarioSinPermiso = User::factory()->create();

    expect(fn () => $servicio->publicarCorte($corte, $usuarioSinPermiso))
        ->toThrow(CorteReportabilidadException::class);
    expect($corte->refresh()->estado)->toBe('borrador');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $publicado = $servicio->publicarCorte($corte, $admin);

    expect($publicado->estado)->toBe('publicado');
    expect($publicado->publicado_por)->toBe($admin->id);
    expect($publicado->publicado_en)->not->toBeNull();
});

test('agregarItem y capturarSnapshot rechazan operar sobre un corte ya publicado', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $servicio = app(CorteReportabilidadService::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $periodo = $servicio->abrirPeriodo('2026-08', '2026-08-01', '2026-08-31');
    $corte = $servicio->crearCorte($periodo);
    $servicio->publicarCorte($corte, $admin);

    expect(fn () => $servicio->agregarItem($corte->refresh(), $admin, 'usuarios'))
        ->toThrow(CorteReportabilidadException::class);

    expect(fn () => $servicio->capturarSnapshot($corte->refresh(), ['ejemplo' => true]))
        ->toThrow(CorteReportabilidadException::class);

    expect($corte->items)->toHaveCount(0);
    expect($corte->snapshots)->toHaveCount(0);
});
