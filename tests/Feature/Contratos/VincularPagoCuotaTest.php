<?php

use App\Exceptions\ContratoException;
use App\Models\AuditLog;
use App\Models\CasoPagoProveedor;
use App\Models\ContratoCuota;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\Contratos\ContratoCalendarioPagoService;
use App\Services\Contratos\ContratoService;
use Database\Seeders\WorkflowContratosSeeder;

function crearContratoConUnaCuotaDePrueba(): array
{
    $proveedor = Proveedor::create(['rutproveedor' => '76123456-7', 'nombre' => 'Proveedor SpA']);
    $contrato = app(ContratoService::class)->crear(datosContratoDePrueba([
        'proveedor_id' => $proveedor->id,
        'fecha_inicio_vigencia' => '2026-01-01',
        'fecha_fin_vigencia' => '2026-06-30',
        'tiene_calendario_pago' => true,
        'periodicidad_pago' => 'unica',
        'monto_total' => 500000,
    ]));

    $cuota = app(ContratoCalendarioPagoService::class)->generarCalendario($contrato)->first();

    return [$contrato, $cuota];
}

beforeEach(function () {
    $this->seed(WorkflowContratosSeeder::class);
});

test('vincular una cuota pendiente a un caso de pago la marca como pagada y audita la acción', function () {
    [$contrato, $cuota] = crearContratoConUnaCuotaDePrueba();
    $caso = CasoPagoProveedor::create(['sgf_id' => 'SGF-1', 'monto' => 500000]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('contratos.vincular_pago');

    $response = $this->actingAs($usuario)->post(
        route('contratos.cuotas.vincular_pago', [$contrato, $cuota]),
        ['caso_pago_proveedor_id' => $caso->id],
    );

    $response->assertSessionHasNoErrors();

    $cuota->refresh();
    expect($cuota->estado)->toBe(ContratoCuota::ESTADO_PAGADA);
    expect($cuota->caso_pago_proveedor_id)->toBe($caso->id);
    expect(AuditLog::where('action', 'contrato_cuota.vincular_pago')->exists())->toBeTrue();
});

test('desvincular una cuota pagada la vuelve a dejar pendiente', function () {
    [$contrato, $cuota] = crearContratoConUnaCuotaDePrueba();
    $caso = CasoPagoProveedor::create(['sgf_id' => 'SGF-2', 'monto' => 500000]);

    app(ContratoCalendarioPagoService::class)->vincularPago($cuota, $caso);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('contratos.vincular_pago');

    $response = $this->actingAs($usuario)->delete(route('contratos.cuotas.desvincular_pago', [$contrato, $cuota]));

    $response->assertSessionHasNoErrors();

    $cuota->refresh();
    expect($cuota->estado)->toBe(ContratoCuota::ESTADO_PENDIENTE);
    expect($cuota->caso_pago_proveedor_id)->toBeNull();
});

test('vincular una cuota ya vinculada es rechazado', function () {
    [, $cuota] = crearContratoConUnaCuotaDePrueba();
    $casoUno = CasoPagoProveedor::create(['sgf_id' => 'SGF-3', 'monto' => 500000]);
    $casoDos = CasoPagoProveedor::create(['sgf_id' => 'SGF-4', 'monto' => 500000]);

    app(ContratoCalendarioPagoService::class)->vincularPago($cuota, $casoUno);

    expect(fn () => app(ContratoCalendarioPagoService::class)->vincularPago($cuota->refresh(), $casoDos))
        ->toThrow(ContratoException::class);
});

test('una cuota pendiente cuya fecha de vencimiento ya pasó se presenta como vencida sin persistir el estado', function () {
    [, $cuota] = crearContratoConUnaCuotaDePrueba();
    $cuota->update(['fecha_vencimiento' => now()->subDay()->toDateString()]);

    expect($cuota->refresh()->esta_vencida)->toBeTrue();
    expect($cuota->getRawOriginal('estado'))->toBe(ContratoCuota::ESTADO_PENDIENTE);
});

test('una cuota pagada nunca se presenta como vencida aunque su fecha ya pasó', function () {
    [, $cuota] = crearContratoConUnaCuotaDePrueba();
    $caso = CasoPagoProveedor::create(['sgf_id' => 'SGF-5', 'monto' => 500000]);

    app(ContratoCalendarioPagoService::class)->vincularPago($cuota, $caso);
    $cuota->update(['fecha_vencimiento' => now()->subDay()->toDateString()]);

    expect($cuota->refresh()->esta_vencida)->toBeFalse();
});

test('vincular una cuota sin el permiso requerido es rechazado', function () {
    [$contrato, $cuota] = crearContratoConUnaCuotaDePrueba();
    $caso = CasoPagoProveedor::create(['sgf_id' => 'SGF-6', 'monto' => 500000]);
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(
        route('contratos.cuotas.vincular_pago', [$contrato, $cuota]),
        ['caso_pago_proveedor_id' => $caso->id],
    );

    $response->assertForbidden();
    expect($cuota->refresh()->caso_pago_proveedor_id)->toBeNull();
});
