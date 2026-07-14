<?php

use App\Models\AuditLog;
use App\Models\CasoPagoProveedor;
use App\Models\SecurityAuditLog;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\TipoProcesoPago;
use App\Models\User;
use App\Services\PagoProveedores\CasoPagoProveedorImporter;
use Database\Seeders\TiposProcesoPagoSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;

function crearCasoPagoProveedorParaClasificacion(string $sgfId = 'sgf-clasificacion-1'): CasoPagoProveedor
{
    $sistema = SistemaExterno::firstOrCreate(
        ['codigo' => 'SGF'],
        ['nombre' => 'SGF', 'tipo_integracion' => 'playwright', 'activo' => true],
    );

    $normalizado = [
        'sgf_id' => $sgfId,
        'estado' => 'EN_TRAMITE',
        'grupo_actual' => 'FINANZAS',
        'observaciones' => null,
        'rut' => '11111111-1',
        'monto' => 500000.0,
    ];

    $snapshot = SnapshotDatosExterno::create([
        'sistema_externo_id' => $sistema->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => $normalizado['sgf_id'],
        'payload_crudo' => $normalizado,
        'payload_normalizado' => $normalizado,
        'hash' => hash('sha256', json_encode($normalizado, JSON_THROW_ON_ERROR)),
        'capturado_en' => now(),
    ]);

    return app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot($snapshot);
}

beforeEach(function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(TiposProcesoPagoSeeder::class);
});

test('clasificar el tipo de proceso con el permiso requerido persiste el valor y registra auditoría', function () {
    $caso = crearCasoPagoProveedorParaClasificacion();
    $tipo = TipoProcesoPago::where('codigo', 'CONTRATO')->firstOrFail();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.gestionar_caso');

    $response = $this->actingAs($usuario)->post(
        route('pago-proveedores.casos.tipo-proceso-pago.store', $caso),
        ['tipo_proceso_pago_id' => $tipo->id],
    );

    $response->assertSessionHasNoErrors();
    expect($caso->proceso->refresh()->tipo_proceso_pago_id)->toBe($tipo->id);

    $registro = AuditLog::where('action', 'caso_pago_proveedor.clasificar_tipo_proceso_pago')->first();
    expect($registro)->not->toBeNull();
    expect($registro->user_id)->toBe($usuario->id);
    expect($registro->before['tipo_proceso_pago_id'])->toBeNull();
    expect($registro->after['tipo_proceso_pago_id'])->toBe($tipo->id);
});

test('un usuario sin el permiso no puede clasificar y queda auditado como acceso denegado', function () {
    $caso = crearCasoPagoProveedorParaClasificacion();
    $tipo = TipoProcesoPago::where('codigo', 'CONTRATO')->firstOrFail();

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(
        route('pago-proveedores.casos.tipo-proceso-pago.store', $caso),
        ['tipo_proceso_pago_id' => $tipo->id],
    );

    $response->assertForbidden();
    expect($caso->proceso->refresh()->tipo_proceso_pago_id)->toBeNull();
    expect(SecurityAuditLog::where('event', 'acceso_denegado')->exists())->toBeTrue();
});

test('reclasificar un caso ya clasificado actualiza el valor y audita el cambio', function () {
    $caso = crearCasoPagoProveedorParaClasificacion();
    $tipoInicial = TipoProcesoPago::where('codigo', 'COMPRA')->firstOrFail();
    $tipoNuevo = TipoProcesoPago::where('codigo', 'CONVENIO')->firstOrFail();
    $caso->proceso->update(['tipo_proceso_pago_id' => $tipoInicial->id]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.gestionar_caso');

    $response = $this->actingAs($usuario)->post(
        route('pago-proveedores.casos.tipo-proceso-pago.store', $caso),
        ['tipo_proceso_pago_id' => $tipoNuevo->id],
    );

    $response->assertSessionHasNoErrors();
    expect($caso->proceso->refresh()->tipo_proceso_pago_id)->toBe($tipoNuevo->id);

    $registro = AuditLog::where('action', 'caso_pago_proveedor.clasificar_tipo_proceso_pago')->latest('id')->first();
    expect($registro->before['tipo_proceso_pago_id'])->toBe($tipoInicial->id);
    expect($registro->after['tipo_proceso_pago_id'])->toBe($tipoNuevo->id);
});

test('clasificar con un tipo de proceso inactivo es rechazado', function () {
    $caso = crearCasoPagoProveedorParaClasificacion();
    $tipoInactivo = TipoProcesoPago::create(['codigo' => 'INACTIVO_TEST', 'nombre' => 'Inactivo', 'activo' => false]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.gestionar_caso');

    $response = $this->actingAs($usuario)->post(
        route('pago-proveedores.casos.tipo-proceso-pago.store', $caso),
        ['tipo_proceso_pago_id' => $tipoInactivo->id],
    );

    $response->assertSessionHasErrors('tipo_proceso_pago_id');
    expect($caso->proceso->refresh()->tipo_proceso_pago_id)->toBeNull();
});
