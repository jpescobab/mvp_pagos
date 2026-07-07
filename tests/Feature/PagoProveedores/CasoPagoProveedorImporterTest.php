<?php

use App\Models\CasoPagoProveedor;
use App\Models\EgresoCgu;
use App\Models\Proveedor;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Services\PagoProveedores\CasoPagoProveedorImporter;
use App\Services\Workflow\TransicionWorkflowService;
use Database\Seeders\WorkflowPagoProveedoresSeeder;

/**
 * @param  array<string, mixed>  $overrides
 */
function crearSnapshotSgf(array $overrides = []): SnapshotDatosExterno
{
    $sistema = SistemaExterno::firstOrCreate(
        ['codigo' => 'SGF'],
        ['nombre' => 'SGF', 'tipo_integracion' => 'playwright', 'activo' => true],
    );

    $normalizado = array_merge([
        'sgf_id' => '12345',
        'estado' => 'EN_TRAMITE',
        'grupo_actual' => 'FINANZAS',
        'observaciones' => null,
        'rut' => '11111111-1',
        'monto' => 500000.0,
    ], $overrides);

    return SnapshotDatosExterno::create([
        'sistema_externo_id' => $sistema->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => $normalizado['sgf_id'],
        'payload_crudo' => $normalizado,
        'payload_normalizado' => $normalizado,
        'hash' => hash('sha256', json_encode($normalizado, JSON_THROW_ON_ERROR)),
        'capturado_en' => now(),
    ]);
}

test('importar un snapshot nuevo crea un caso de pago y su proceso en el estado inicial', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $snapshot = crearSnapshotSgf();

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot($snapshot);

    expect($caso->sgf_id)->toBe('12345');
    expect($caso->monto)->toEqual(500000.0);
    expect($caso->proceso)->not->toBeNull();
    expect($caso->proceso->estadoActual->codigo)->toBe('importada_desde_sgf');
});

test('reimportar un sgf_id existente actualiza la referencia SGF sin alterar el estado del proceso', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgf());

    app(TransicionWorkflowService::class)->execute($caso->proceso, 'recibir_en_finanzas');

    $casoActualizado = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(
        crearSnapshotSgf(['estado' => 'PAGADA', 'monto' => 750000.0]),
    );

    expect($casoActualizado->id)->toBe($caso->id);
    expect($casoActualizado->sgf_status)->toBe('PAGADA');
    expect($casoActualizado->monto)->toEqual(750000.0);
    expect($casoActualizado->proceso->refresh()->estadoActual->codigo)->toBe('recibida_finanzas');
    expect($casoActualizado->proceso->monto)->toEqual(750000.0);
});

test('el proveedor_id se resuelve cuando el RUT coincide y queda null si no hay coincidencia', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $proveedor = Proveedor::create(['rutproveedor' => '22222222-2', 'nombre' => 'Proveedor de prueba']);

    $casoConProveedor = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(
        crearSnapshotSgf(['sgf_id' => 'con-proveedor', 'rut' => '22222222-2']),
    );
    $casoSinProveedor = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(
        crearSnapshotSgf(['sgf_id' => 'sin-proveedor', 'rut' => '99999999-9']),
    );

    expect($casoConProveedor->proveedor_id)->toBe($proveedor->id);
    expect($casoSinProveedor->proveedor_id)->toBeNull();
});

test('el workflow pago_proveedores sembrado permite ejecutar una transición real', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgf());

    $resultado = app(TransicionWorkflowService::class)->execute($caso->proceso, 'recibir_en_finanzas');

    expect($resultado->estadoActual->codigo)->toBe('recibida_finanzas');
});

test('egresos_cgu_items asocia un egreso a varios casos', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $casoUno = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgf(['sgf_id' => 'caso-1']));
    $casoDos = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgf(['sgf_id' => 'caso-2']));

    $egreso = EgresoCgu::create(['numero_egreso' => 'EGR-001', 'fecha' => now()]);
    $egreso->items()->create(['caso_pago_proveedor_id' => $casoUno->id, 'monto' => 300000]);
    $egreso->items()->create(['caso_pago_proveedor_id' => $casoDos->id, 'monto' => 200000]);

    expect($egreso->items)->toHaveCount(2);
    expect(CasoPagoProveedor::find($casoUno->id)->exists())->toBeTrue();
});
