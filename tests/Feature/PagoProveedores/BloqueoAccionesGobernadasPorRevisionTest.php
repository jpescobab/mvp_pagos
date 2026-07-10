<?php

use App\Models\CasoPagoProveedor;
use App\Models\Documento;
use App\Models\EstadoWorkflow;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\User;
use App\Services\PagoProveedores\CasoPagoProveedorImporter;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;

/**
 * @param  array<string, mixed>  $overrides
 */
function crearSnapshotSgfParaBloqueo(array $overrides = []): SnapshotDatosExterno
{
    $sistema = SistemaExterno::firstOrCreate(
        ['codigo' => 'SGF'],
        ['nombre' => 'SGF', 'tipo_integracion' => 'playwright', 'activo' => true],
    );

    $normalizado = array_merge([
        'sgf_id' => 'bloqueo-'.fake()->unique()->numerify('####'),
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

function forzarEstadoCaso(CasoPagoProveedor $caso, string $codigoEstado): void
{
    $estadoId = EstadoWorkflow::where('codigo', $codigoEstado)
        ->where('definicion_workflow_id', $caso->proceso->definicion_workflow_id)
        ->value('id');

    $caso->proceso->update(['estado_actual_id' => $estadoId]);
}

test('las transiciones gobernadas por la revision de Finanzas son rechazadas desde el endpoint generico del caso', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgfParaBloqueo());
    forzarEstadoCaso($caso, 'en_revision_finanzas');

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.revisar_finanzas');

    foreach (['observar_finanzas', 'aprobar_finanzas', 'rechazar_finanzas'] as $codigo) {
        $response = $this->actingAs($usuario)->post(
            route('pago-proveedores.casos.transiciones.store', $caso),
            ['codigo' => $codigo, 'comentario' => 'intento vía pantalla de caso'],
        );

        $response->assertSessionHasErrors('transicion');
    }

    expect($caso->proceso->refresh()->estadoActual->codigo)->toBe('en_revision_finanzas');
});

test('las transiciones gobernadas por la revision Zonal son rechazadas desde el endpoint generico del caso', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgfParaBloqueo());
    forzarEstadoCaso($caso, 'en_revision_zonal');

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.revisar_zonal');

    foreach (['devolver_a_finanzas', 'aprobar_zonal', 'rechazar_zonal'] as $codigo) {
        $response = $this->actingAs($usuario)->post(
            route('pago-proveedores.casos.transiciones.store', $caso),
            ['codigo' => $codigo, 'comentario' => 'intento vía pantalla de caso'],
        );

        $response->assertSessionHasErrors('transicion');
    }

    expect($caso->proceso->refresh()->estadoActual->codigo)->toBe('en_revision_zonal');
});

test('validar un documento desde el endpoint generico es rechazado mientras el caso esta en revision', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgfParaBloqueo());
    forzarEstadoCaso($caso, 'en_revision_finanzas');

    $tipoDocumento = crearTipoDocumentoDePrueba();
    $documento = Documento::create(['tipo_documento_id' => $tipoDocumento->id, 'titulo' => 'factura.pdf']);
    $caso->proceso->vinculosDocumento()->create(['documento_id' => $documento->id, 'activo' => true]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('documentos.validar');

    $response = $this->actingAs($usuario)->post(
        route('procesos.documentos.validaciones.store', [$caso->proceso, $documento]),
        ['estado' => 'valido'],
    );

    $response->assertForbidden();
    expect($documento->validaciones)->toHaveCount(0);
});

test('validar un documento desde el endpoint generico sigue funcionando fuera de la ventana de revision', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgfParaBloqueo());

    $tipoDocumento = crearTipoDocumentoDePrueba();
    $documento = Documento::create(['tipo_documento_id' => $tipoDocumento->id, 'titulo' => 'factura.pdf']);
    $caso->proceso->vinculosDocumento()->create(['documento_id' => $documento->id, 'activo' => true]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('documentos.validar');

    $response = $this->actingAs($usuario)->post(
        route('procesos.documentos.validaciones.store', [$caso->proceso, $documento]),
        ['estado' => 'valido'],
    );

    $response->assertSessionHasNoErrors();
    expect($documento->refresh()->estadoVigente())->toBe('valido');
});
