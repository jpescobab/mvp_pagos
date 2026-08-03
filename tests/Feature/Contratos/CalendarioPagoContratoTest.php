<?php

use App\Exceptions\ContratoException;
use App\Models\Proveedor;
use App\Services\Contratos\ContratoCalendarioPagoService;
use App\Services\Contratos\ContratoService;
use App\Services\Workflow\TransicionWorkflowService;
use Database\Seeders\WorkflowContratosSeeder;

beforeEach(function () {
    $this->seed(WorkflowContratosSeeder::class);
});

test('generar un calendario mensual sobre 12 meses de vigencia crea 12 cuotas que suman el monto total', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76123456-7', 'nombre' => 'Proveedor SpA']);
    $contrato = app(ContratoService::class)->crear(datosContratoDePrueba([
        'proveedor_id' => $proveedor->id,
        'fecha_inicio_vigencia' => '2026-01-01',
        'fecha_fin_vigencia' => '2027-01-01',
        'tiene_calendario_pago' => true,
        'periodicidad_pago' => 'mensual',
        'monto_total' => 1200000,
    ]));

    $cuotas = app(ContratoCalendarioPagoService::class)->generarCalendario($contrato);

    expect($cuotas)->toHaveCount(12);
    expect((float) $cuotas->sum('monto'))->toBe(1200000.0);
    expect($cuotas->last()->fecha_vencimiento->toDateString())->toBe('2027-01-01');
});

test('periodicidad única genera una sola cuota con el monto total en la fecha de fin de vigencia', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76123456-7', 'nombre' => 'Proveedor SpA']);
    $contrato = app(ContratoService::class)->crear(datosContratoDePrueba([
        'proveedor_id' => $proveedor->id,
        'fecha_inicio_vigencia' => '2026-01-01',
        'fecha_fin_vigencia' => '2026-06-30',
        'tiene_calendario_pago' => true,
        'periodicidad_pago' => 'unica',
        'monto_total' => 500000,
    ]));

    $cuotas = app(ContratoCalendarioPagoService::class)->generarCalendario($contrato);

    expect($cuotas)->toHaveCount(1);
    expect($cuotas->first()->fecha_vencimiento->toDateString())->toBe('2026-06-30');
    expect((float) $cuotas->first()->monto)->toBe(500000.0);
});

test('generar el calendario sin monto_total es rechazado', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76123456-7', 'nombre' => 'Proveedor SpA']);
    $contrato = app(ContratoService::class)->crear(datosContratoDePrueba([
        'proveedor_id' => $proveedor->id,
        'tiene_calendario_pago' => true,
        'periodicidad_pago' => 'mensual',
    ]));

    expect(fn () => app(ContratoCalendarioPagoService::class)->generarCalendario($contrato))
        ->toThrow(ContratoException::class);
});

test('editar una cuota individual solo es posible mientras el contrato esté en borrador', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76123456-7', 'nombre' => 'Proveedor SpA']);
    $contrato = app(ContratoService::class)->crear(datosContratoDePrueba([
        'proveedor_id' => $proveedor->id,
        'fecha_inicio_vigencia' => '2026-01-01',
        'fecha_fin_vigencia' => '2026-06-30',
        'tiene_calendario_pago' => true,
        'periodicidad_pago' => 'unica',
        'monto_total' => 500000,
    ]));

    $calendarioService = app(ContratoCalendarioPagoService::class);
    $cuota = $calendarioService->generarCalendario($contrato)->first();

    $cuotaActualizada = $calendarioService->actualizarCuota($cuota, ['fecha_vencimiento' => '2026-07-15', 'monto' => 450000]);
    expect($cuotaActualizada->monto)->toBe('450000.00');

    app(TransicionWorkflowService::class)->execute($contrato->proceso, 'pendiente');

    expect(fn () => $calendarioService->actualizarCuota($cuota->refresh(), ['fecha_vencimiento' => '2026-08-01', 'monto' => 100]))
        ->toThrow(ContratoException::class);
});
