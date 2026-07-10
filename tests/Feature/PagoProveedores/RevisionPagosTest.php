<?php

use App\Enums\PagoProveedores\InstanciaRevision;
use App\Exceptions\TransicionWorkflowException;
use App\Models\CasoPagoProveedor;
use App\Models\DefinicionWorkflow;
use App\Models\Documento;
use App\Models\EgresoCgu;
use App\Models\EgresoCguItem;
use App\Models\Institucion;
use App\Models\ModalidadAdquisicion;
use App\Models\Proceso;
use App\Models\ProcesoAdquisicion;
use App\Models\Proveedor;
use App\Models\SecurityAuditLog;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\TipoDocumento;
use App\Models\User;
use App\Services\PagoProveedores\CasoPagoProveedorImporter;
use App\Services\PagoProveedores\RevisionEgresoService;
use App\Services\PagoProveedores\ValidacionDocumentoInstanciaService;
use App\Services\Workflow\TransicionWorkflowService;
use Database\Seeders\ModalidadesAdquisicionSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TiposDocumentoSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;

/**
 * Crea un escenario de revisión: un egreso con un caso en `en_revision_finanzas`,
 * con su proceso de adquisición (para derivar el centro financiero), factura,
 * registro contable y un documento FACTURA vinculado.
 *
 * @return array{egreso: EgresoCgu, caso: CasoPagoProveedor, documento: Documento, cfinancieroId: int, jurisdiccionId: int}
 */
function crearEscenarioRevision(float $monto = 500000, string $sufijo = 'A'): array
{
    $institucion = Institucion::create(['codigo' => "CAPJ-{$sufijo}", 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => "14-{$sufijo}", 'nombre' => "Zonal {$sufijo}"]);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => "CF-{$sufijo}", 'nombre' => "Centro {$sufijo}"]);
    $ccosto = $cfinanciero->ccostos()->create(['codigo' => "CC-{$sufijo}", 'nombre' => "Costo {$sufijo}"]);

    $proveedor = Proveedor::create([
        'rutproveedor' => fake()->unique()->numerify('########-#'),
        'nombre' => "Proveedor {$sufijo}",
        'activo' => true,
    ]);

    $adquisicion = ProcesoAdquisicion::create([
        'codigo' => "ADQ-{$sufijo}",
        'modalidad_id' => ModalidadAdquisicion::where('codigo', 'TRATO_DIRECTO')->value('id'),
        'ccosto_id' => $ccosto->id,
        'proveedor_id' => $proveedor->id,
        'objeto' => 'Compra de prueba',
    ]);

    $caso = CasoPagoProveedor::create([
        'sgf_id' => "SGF-{$sufijo}",
        'proceso_adquisicion_id' => $adquisicion->id,
        'proveedor_id' => $proveedor->id,
        'rut_proveedor' => $proveedor->rutproveedor,
        'monto' => $monto,
        'sgf_status' => 'EN_TRAMITE',
        'folio_egreso' => "EGR-{$sufijo}",
        'periodo' => '2026-07',
    ]);

    $definicion = DefinicionWorkflow::where('codigo', 'pago_proveedores')->firstOrFail();
    $proceso = Proceso::create([
        'definicion_workflow_id' => $definicion->id,
        'estado_actual_id' => $definicion->estados()->where('es_inicial', true)->value('id'),
        'sujeto_type' => CasoPagoProveedor::class,
        'sujeto_id' => $caso->id,
        'monto' => $monto,
    ]);

    $workflow = app(TransicionWorkflowService::class);
    $proceso = $workflow->execute($proceso, 'recibir_en_finanzas');
    $workflow->execute($proceso, 'iniciar_revision_documental');

    $caso->facturas()->create(['proveedor_id' => $proveedor->id, 'folio' => "F-{$sufijo}", 'monto' => $monto, 'fecha_emision' => '2026-07-01']);
    $caso->registrosContablesCgu()->create(['numero_registro' => "RC-{$sufijo}", 'fecha_registro' => '2026-07-02', 'monto' => $monto]);

    $factura = TipoDocumento::where('codigo', 'FACTURA')->firstOrFail();
    $documento = Documento::create(['tipo_documento_id' => $factura->id, 'titulo' => "Factura {$sufijo}.pdf"]);
    $proceso->vinculosDocumento()->create(['documento_id' => $documento->id, 'activo' => true]);

    $egreso = EgresoCgu::create([
        'numero_egreso' => "EGR-{$sufijo}",
        'fecha' => '2026-07-05',
        'monto_total' => $monto,
        'periodo' => '2026-07',
        'cfinanciero_id' => $cfinanciero->id,
        'generado_automaticamente' => true,
    ]);
    EgresoCguItem::create(['egreso_cgu_id' => $egreso->id, 'caso_pago_proveedor_id' => $caso->id, 'monto' => $monto]);

    return [
        'egreso' => $egreso->refresh(),
        'caso' => $caso->refresh(),
        'documento' => $documento,
        'cfinancieroId' => $cfinanciero->id,
        'jurisdiccionId' => $jurisdiccion->id,
    ];
}

function usuarioConRol(string $rol, ?int $cfinancieroId = null): User
{
    $user = User::factory()->create();
    $user->assignRole($rol);

    if ($cfinancieroId !== null) {
        $user->funcionario()->create([
            'rut' => fake()->unique()->numerify('########-#'),
            'nombre' => 'Funcionario',
            'cfinanciero_id' => $cfinancieroId,
            'activo' => true,
        ]);
    }

    return $user->refresh();
}

beforeEach(function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(TiposDocumentoSeeder::class);
    $this->seed(ModalidadesAdquisicionSeeder::class);
});

test('el flujo feliz recorre Finanzas -> Zonal -> lista para registro CGU', function () {
    $e = crearEscenarioRevision();
    $revision = app(RevisionEgresoService::class);
    $validaciones = app(ValidacionDocumentoInstanciaService::class);

    $finanzas = usuarioConRol('jefe_finanzas');
    $zonal = usuarioConRol('administrador_zonal', $e['cfinancieroId']);

    // Instancia Finanzas: valida documento, verifica totales y aprueba.
    $validaciones->validar($e['documento'], InstanciaRevision::Finanzas, 'valido', null, $finanzas);
    $revision->verificarTotales($e['caso'], InstanciaRevision::Finanzas, $finanzas);
    $revision->aprobarPago($e['caso']->refresh(), $finanzas);

    expect($e['caso']->proceso->refresh()->estadoActual->codigo)->toBe('en_revision_zonal');

    // Instancia Zonal: revisa el documento de nuevo, verifica totales y aprueba.
    $caso = $e['caso']->refresh();
    $validaciones->validar($e['documento'], InstanciaRevision::Zonal, 'valido', null, $zonal);
    $revision->verificarTotales($caso, InstanciaRevision::Zonal, $zonal);
    $revision->aprobarPago($caso->refresh(), $zonal);

    expect($caso->proceso->refresh()->estadoActual->codigo)->toBe('lista_para_registro_cgu');
});

test('la instancia Zonal puede devolver el pago a Finanzas con comentario', function () {
    $e = crearEscenarioRevision();
    $revision = app(RevisionEgresoService::class);
    $validaciones = app(ValidacionDocumentoInstanciaService::class);
    $finanzas = usuarioConRol('jefe_finanzas');
    $zonal = usuarioConRol('administrador_zonal', $e['cfinancieroId']);

    $validaciones->validar($e['documento'], InstanciaRevision::Finanzas, 'valido', null, $finanzas);
    $revision->verificarTotales($e['caso'], InstanciaRevision::Finanzas, $finanzas);
    $revision->aprobarPago($e['caso']->refresh(), $finanzas);

    $revision->devolverPago($e['caso']->refresh(), 'Falta timbre en la factura.', $zonal);

    expect($e['caso']->proceso->refresh()->estadoActual->codigo)->toBe('en_revision_finanzas');
});

test('devolver un pago sin comentario vía HTTP es rechazado', function () {
    $e = crearEscenarioRevision();
    $revision = app(RevisionEgresoService::class);
    $validaciones = app(ValidacionDocumentoInstanciaService::class);
    $finanzas = usuarioConRol('jefe_finanzas');
    $zonal = usuarioConRol('administrador_zonal', $e['cfinancieroId']);

    $validaciones->validar($e['documento'], InstanciaRevision::Finanzas, 'valido', null, $finanzas);
    $revision->verificarTotales($e['caso'], InstanciaRevision::Finanzas, $finanzas);
    $revision->aprobarPago($e['caso']->refresh(), $finanzas);

    $response = $this->actingAs($zonal)->post(
        route('pago-proveedores.revision.pagos.transicion', ['egresoCgu' => $e['egreso']->id, 'caso' => $e['caso']->id]),
        ['accion' => 'devolver'],
    );

    $response->assertSessionHasErrors('comentario');
    expect($e['caso']->proceso->refresh()->estadoActual->codigo)->toBe('en_revision_zonal');
});

test('no se puede aprobar un pago con el documento sin aprobar o totales sin verificar', function () {
    $e = crearEscenarioRevision();
    $revision = app(RevisionEgresoService::class);
    $finanzas = usuarioConRol('jefe_finanzas');

    // Sin validar documento ni verificar totales.
    expect(fn () => $revision->aprobarPago($e['caso']->refresh(), $finanzas))
        ->toThrow(RuntimeException::class);

    expect($e['caso']->proceso->refresh()->estadoActual->codigo)->toBe('en_revision_finanzas');
});

test('la validación de Finanzas no altera el estado del documento para Zonal y el historial conserva ambos', function () {
    $e = crearEscenarioRevision();
    $revision = app(RevisionEgresoService::class);
    $validaciones = app(ValidacionDocumentoInstanciaService::class);
    $finanzas = usuarioConRol('jefe_finanzas');
    $zonal = usuarioConRol('administrador_zonal', $e['cfinancieroId']);

    $validaciones->validar($e['documento'], InstanciaRevision::Finanzas, 'valido', null, $finanzas);
    $revision->verificarTotales($e['caso'], InstanciaRevision::Finanzas, $finanzas);
    $revision->aprobarPago($e['caso']->refresh(), $finanzas);

    $documento = $e['documento']->load('validaciones');

    expect($validaciones->estadoVigente($documento, InstanciaRevision::Finanzas))->toBe('valido');
    expect($validaciones->estadoVigente($documento, InstanciaRevision::Zonal))->toBe('pendiente');

    // El Zonal rechaza; el evento de Finanzas sigue registrado.
    $validaciones->validar($documento, InstanciaRevision::Zonal, 'rechazado', 'Diferencia de monto', $zonal);
    $documento->load('validaciones');

    expect($documento->validaciones)->toHaveCount(2);
    expect($validaciones->estadoVigente($documento, InstanciaRevision::Finanzas))->toBe('valido');
    expect($validaciones->estadoVigente($documento, InstanciaRevision::Zonal))->toBe('rechazado');
});

test('el egreso solo avanza cuando todos sus pagos están aprobados en la instancia actual', function () {
    $e = crearEscenarioRevision(500000, 'A');
    $revision = app(RevisionEgresoService::class);
    $validaciones = app(ValidacionDocumentoInstanciaService::class);
    $finanzas = usuarioConRol('jefe_finanzas');

    // Segundo pago en el mismo egreso, sin preparar.
    $segundo = crearEscenarioRevision(300000, 'B');
    EgresoCguItem::create(['egreso_cgu_id' => $e['egreso']->id, 'caso_pago_proveedor_id' => $segundo['caso']->id, 'monto' => 300000]);

    // Solo el primero está listo.
    $validaciones->validar($e['documento'], InstanciaRevision::Finanzas, 'valido', null, $finanzas);
    $revision->verificarTotales($e['caso'], InstanciaRevision::Finanzas, $finanzas);

    expect(fn () => $revision->aprobarEgreso($e['egreso']->refresh(), $finanzas))
        ->toThrow(RuntimeException::class);

    expect($e['caso']->proceso->refresh()->estadoActual->codigo)->toBe('en_revision_finanzas');
});

test('un Administrador Zonal no puede abrir un egreso de otra jurisdicción y queda auditado', function () {
    $e = crearEscenarioRevision();

    // Instancia activa Zonal: aprueba Finanzas primero.
    $revision = app(RevisionEgresoService::class);
    $validaciones = app(ValidacionDocumentoInstanciaService::class);
    $finanzas = usuarioConRol('jefe_finanzas');
    $validaciones->validar($e['documento'], InstanciaRevision::Finanzas, 'valido', null, $finanzas);
    $revision->verificarTotales($e['caso'], InstanciaRevision::Finanzas, $finanzas);
    $revision->aprobarPago($e['caso']->refresh(), $finanzas);

    // Zonal de OTRA jurisdicción (otro centro financiero).
    $otro = crearEscenarioRevision(1, 'Z');
    $zonalOtraZona = usuarioConRol('administrador_zonal', $otro['cfinancieroId']);

    $response = $this->actingAs($zonalOtraZona)->get(
        route('pago-proveedores.revision.show', ['egresoCgu' => $e['egreso']->id]),
    );

    $response->assertForbidden();
    expect(SecurityAuditLog::where('event', 'acceso_denegado')->exists())->toBeTrue();
});

test('la aprobación de Finanzas exige el permiso revisar_finanzas', function () {
    $e = crearEscenarioRevision();
    $revision = app(RevisionEgresoService::class);
    $validaciones = app(ValidacionDocumentoInstanciaService::class);
    $sinPermiso = User::factory()->create();

    $finanzas = usuarioConRol('jefe_finanzas');
    $validaciones->validar($e['documento'], InstanciaRevision::Finanzas, 'valido', null, $finanzas);
    $revision->verificarTotales($e['caso'], InstanciaRevision::Finanzas, $finanzas);

    expect(fn () => $revision->aprobarPago($e['caso']->refresh(), $sinPermiso))
        ->toThrow(TransicionWorkflowException::class);
});

test('al importar desde SGF los pagos con el mismo folio de egreso se agrupan en un Egreso', function () {
    $sistema = SistemaExterno::firstOrCreate(
        ['codigo' => 'SGF'],
        ['nombre' => 'SGF', 'tipo_integracion' => 'playwright', 'activo' => true],
    );
    $importer = app(CasoPagoProveedorImporter::class);

    $importarCaso = function (string $sgfId, ?string $folio) use ($sistema, $importer) {
        $normalizado = [
            'sgf_id' => $sgfId,
            'estado' => 'EN_TRAMITE',
            'grupo_actual' => 'FINANZAS',
            'rut' => fake()->unique()->numerify('########-#'),
            'monto' => 100000.0,
            'folio_egreso' => $folio,
        ];
        $snapshot = SnapshotDatosExterno::create([
            'sistema_externo_id' => $sistema->id,
            'metodo_captura' => 'playwright',
            'referencia_externa' => $sgfId,
            'payload_crudo' => $normalizado,
            'payload_normalizado' => $normalizado,
            'hash' => hash('sha256', $sgfId),
            'capturado_en' => now(),
        ]);

        return $importer->importarDesdeSnapshot($snapshot);
    };

    $importarCaso('SGF-G1', 'EGR-GRUPO');
    $importarCaso('SGF-G2', 'EGR-GRUPO');
    $importarCaso('SGF-SOLO', null);

    $egreso = EgresoCgu::where('numero_egreso', 'EGR-GRUPO')->first();

    expect($egreso)->not->toBeNull();
    expect($egreso->generado_automaticamente)->toBeTrue();
    expect($egreso->items()->count())->toBe(2);
    expect((float) $egreso->monto_total)->toBe(200000.0);
    expect(EgresoCgu::count())->toBe(1); // el caso sin folio no crea egreso
});
