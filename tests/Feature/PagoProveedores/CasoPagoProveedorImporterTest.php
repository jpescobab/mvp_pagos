<?php

use App\Models\CasoPagoProveedor;
use App\Models\EgresoCgu;
use App\Models\Proveedor;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\User;
use App\Services\PagoProveedores\CasoPagoProveedorImporter;
use App\Services\Workflow\TransicionWorkflowService;
use Database\Seeders\WorkflowPagoProveedoresSeeder;

function usuarioQuePuedeGestionarCaso(): User
{
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.gestionar_caso');

    return $usuario;
}

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
        'observacion' => null,
        'rut' => '11111111-1',
        'monto' => 500000.0,
        'periodo' => '2026-07',
        'folio_egreso' => 'EGR-9001',
        'numero' => '4521',
        'fecha_sii' => '08-07-2026',
        'observacion_egreso' => 'EGRESO-115',
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

    app(TransicionWorkflowService::class)->execute($caso->proceso, 'recibir_en_finanzas', user: usuarioQuePuedeGestionarCaso());

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

test('el proveedor_id se resuelve aunque el RUT de SGF venga con puntos y el de proveedores sin puntos', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $proveedor = Proveedor::create(['rutproveedor' => '9317442-9', 'nombre' => 'Proveedor con RUT normalizado']);

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(
        crearSnapshotSgf(['sgf_id' => 'rut-con-puntos', 'rut' => '9.317.442-9']),
    );

    expect($caso->proveedor_id)->toBe($proveedor->id);
});

test('el workflow pago_proveedores sembrado permite ejecutar una transición real', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgf());

    $resultado = app(TransicionWorkflowService::class)->execute($caso->proceso, 'recibir_en_finanzas', user: usuarioQuePuedeGestionarCaso());

    expect($resultado->estadoActual->codigo)->toBe('recibida_finanzas');
});

test('reimportar un sgf_id existente actualiza periodo, observacion, folio_egreso, numero y fecha_sii sin alterar el proceso', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgf());

    app(TransicionWorkflowService::class)->execute($caso->proceso, 'recibir_en_finanzas', user: usuarioQuePuedeGestionarCaso());

    $casoActualizado = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(
        crearSnapshotSgf([
            'periodo' => '2026-08',
            'observacion' => 'Reenviado por diferencia de monto',
            'folio_egreso' => 'EGR-9002',
            'numero' => '4600',
            'fecha_sii' => '15-08-2026',
        ]),
    );

    expect($casoActualizado->id)->toBe($caso->id);
    expect($casoActualizado->periodo)->toBe('2026-08');
    expect($casoActualizado->observacion)->toBe('Reenviado por diferencia de monto');
    expect($casoActualizado->folio_egreso)->toBe('EGR-9002');
    expect($casoActualizado->numero)->toBe('4600');
    expect($casoActualizado->fecha_sii->toDateString())->toBe('2026-08-15');
    expect($casoActualizado->proceso->refresh()->estadoActual->codigo)->toBe('recibida_finanzas');
});

test('un payload normalizado sin los campos nuevos deja esos campos en null sin fallar la importación', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgf([
        'periodo' => null,
        'observacion' => null,
        'folio_egreso' => null,
        'numero' => null,
        'fecha_sii' => null,
        'observacion_egreso' => null,
    ]));

    expect($caso->periodo)->toBeNull();
    expect($caso->observacion)->toBeNull();
    expect($caso->folio_egreso)->toBeNull();
    expect($caso->numero)->toBeNull();
    expect($caso->fecha_sii)->toBeNull();
    expect($caso->observacion_egreso)->toBeNull();
});

test('reimportar un sgf_id existente actualiza observacion_egreso sin alterar el estado del proceso', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgf(['observacion_egreso' => 'EGRESO-115']));

    app(TransicionWorkflowService::class)->execute($caso->proceso, 'recibir_en_finanzas', user: usuarioQuePuedeGestionarCaso());

    $casoActualizado = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(
        crearSnapshotSgf(['observacion_egreso' => 'EGRESO-200']),
    );

    expect($casoActualizado->id)->toBe($caso->id);
    expect($casoActualizado->observacion_egreso)->toBe('EGRESO-200');
    expect($casoActualizado->proceso->refresh()->estadoActual->codigo)->toBe('recibida_finanzas');
});

test('un payload normalizado sin observacion_egreso deja el campo en null sin fallar la importación', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgf(['observacion_egreso' => null]));

    expect($caso->observacion_egreso)->toBeNull();
});

test('fecha_sii con formato no parseable se guarda como null sin fallar la importación', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgf([
        'fecha_sii' => 'no-es-una-fecha',
    ]));

    expect($caso->fecha_sii)->toBeNull();
});

test('importar un snapshot con numero_traspaso lo persiste como sgf_numero_traspaso', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(
        crearSnapshotSgf(['numero_traspaso' => 'TR-2026-0087']),
    );

    expect($caso->sgf_numero_traspaso)->toBe('TR-2026-0087');
});

test('reimportar un sgf_id existente actualiza sgf_numero_traspaso sin alterar el estado del proceso', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(
        crearSnapshotSgf(['numero_traspaso' => 'TR-2026-0087']),
    );

    app(TransicionWorkflowService::class)->execute($caso->proceso, 'recibir_en_finanzas', user: usuarioQuePuedeGestionarCaso());

    $casoActualizado = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(
        crearSnapshotSgf(['numero_traspaso' => 'TR-2026-0099']),
    );

    expect($casoActualizado->id)->toBe($caso->id);
    expect($casoActualizado->sgf_numero_traspaso)->toBe('TR-2026-0099');
    expect($casoActualizado->proceso->refresh()->estadoActual->codigo)->toBe('recibida_finanzas');
});

test('un payload normalizado sin numero_traspaso deja sgf_numero_traspaso en null sin fallar la importación', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgf());

    expect($caso->sgf_numero_traspaso)->toBeNull();
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
