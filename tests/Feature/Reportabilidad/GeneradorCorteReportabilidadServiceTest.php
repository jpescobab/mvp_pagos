<?php

use App\Exceptions\CorteReportabilidadException;
use App\Models\CasoPagoProveedor;
use App\Services\Reportabilidad\CorteReportabilidadService;
use App\Services\Reportabilidad\GeneradorCorteReportabilidadService;

function corteEnBorradorParaGenerar(string $codigoPeriodo = '2026-06')
{
    $cortes = app(CorteReportabilidadService::class);
    $periodo = $cortes->abrirPeriodo($codigoPeriodo, "{$codigoPeriodo}-01", "{$codigoPeriodo}-28");

    return $cortes->crearCorte($periodo);
}

test('generar puebla el corte con un ítem y un snapshot por cada caso del período', function () {
    $corte = corteEnBorradorParaGenerar('2026-06');

    CasoPagoProveedor::create(['sgf_id' => 'SGF-1', 'periodo' => '2026-06', 'monto' => 100]);
    CasoPagoProveedor::create(['sgf_id' => 'SGF-2', 'periodo' => '2026-06', 'monto' => 200]);
    // Caso de otro período: no debe entrar al corte.
    CasoPagoProveedor::create(['sgf_id' => 'SGF-3', 'periodo' => '2026-07', 'monto' => 300]);

    app(GeneradorCorteReportabilidadService::class)->generar($corte);

    expect($corte->items()->count())->toBe(2);
    expect($corte->snapshots()->count())->toBe(2);

    $snapshot = $corte->snapshots()->first();
    expect($snapshot->hash)->not->toBeEmpty();
    expect($snapshot->corte_reportabilidad_item_id)->not->toBeNull();
    expect($snapshot->payload_crudo['sgf_id'])->toBeIn(['SGF-1', 'SGF-2']);
});

test('regenerar reemplaza el contenido previo sin duplicar', function () {
    $corte = corteEnBorradorParaGenerar('2026-06');
    CasoPagoProveedor::create(['sgf_id' => 'SGF-1', 'periodo' => '2026-06', 'monto' => 100]);

    $generador = app(GeneradorCorteReportabilidadService::class);
    $generador->generar($corte);
    expect($corte->items()->count())->toBe(1);

    // Aparece un segundo caso del período y se regenera.
    CasoPagoProveedor::create(['sgf_id' => 'SGF-2', 'periodo' => '2026-06', 'monto' => 200]);
    $generador->generar($corte);

    expect($corte->items()->count())->toBe(2);
    expect($corte->snapshots()->count())->toBe(2);
});

test('generar sobre un período sin casos deja el corte sin contenido', function () {
    $corte = corteEnBorradorParaGenerar('2026-06');

    app(GeneradorCorteReportabilidadService::class)->generar($corte);

    expect($corte->items()->count())->toBe(0);
    expect($corte->snapshots()->count())->toBe(0);
    expect($corte->refresh()->estado)->toBe('borrador');
});

test('generar sobre un corte publicado lanza una excepción', function () {
    $cortes = app(CorteReportabilidadService::class);
    $corte = corteEnBorradorParaGenerar('2026-06');
    $corte->update(['estado' => 'publicado']);

    expect(fn () => app(GeneradorCorteReportabilidadService::class)->generar($corte))
        ->toThrow(CorteReportabilidadException::class);
});
