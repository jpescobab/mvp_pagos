<?php

use App\Enums\PagoProveedores\InstanciaRevision;
use App\Exceptions\TransicionWorkflowException;
use App\Models\CasoPagoProveedor;
use App\Models\ChecklistDocumentalProceso;
use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\DefinicionWorkflow;
use App\Models\Documento;
use App\Models\EgresoCgu;
use App\Models\EgresoCguItem;
use App\Models\Institucion;
use App\Models\ModalidadAdquisicion;
use App\Models\Proceso;
use App\Models\ProcesoAdquisicion;
use App\Models\Proveedor;
use App\Models\RequisitoDocumental;
use App\Models\SecurityAuditLog;
use App\Models\TipoDocumento;
use App\Models\User;
use App\Services\PagoProveedores\RevisionEgresoPresenter;
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

    $preparador = User::factory()->create();
    $preparador->givePermissionTo('pago_proveedores.gestionar_caso');

    $workflow = app(TransicionWorkflowService::class);
    $proceso = $workflow->execute($proceso, 'recibir_en_finanzas', user: $preparador);
    $workflow->execute($proceso, 'iniciar_revision_documental', user: $preparador);

    $caso->facturas()->create(['proveedor_id' => $proveedor->id, 'folio' => "F-{$sufijo}", 'monto' => $monto, 'fecha_emision' => '2026-07-01']);
    $caso->registrosContablesCgu()->create(['numero_registro' => "RC-{$sufijo}", 'fecha_registro' => '2026-07-02', 'monto' => $monto]);

    $factura = TipoDocumento::where('codigo', 'FACTURA')->firstOrFail();
    $documento = Documento::create(['tipo_documento_id' => $factura->id, 'titulo' => "Factura {$sufijo}.pdf"]);
    $proceso->vinculosDocumento()->create(['documento_id' => $documento->id, 'activo' => true]);

    // Checklist del proceso con FACTURA como único obligatorio (el requisito
    // universal que ya exige el workflow). La Revisión de Pagos clasifica y
    // gatea la aprobación contra este checklist.
    $checklist = crearChecklistProceso($proceso, [['codigo' => 'FACTURA', 'tipo_requisito' => 'obligatorio']]);

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
        'checklist' => $checklist,
        'cfinancieroId' => $cfinanciero->id,
        'jurisdiccionId' => $jurisdiccion->id,
    ];
}

/**
 * Crea un ChecklistDocumentalProceso para el proceso con los ítems indicados.
 * La clasificación de la revisión usa los vínculos vivos de documentos, no el
 * documento_id del ítem, así que basta declarar tipo + tipo_requisito.
 *
 * @param  list<array{codigo: string, tipo_requisito: string}>  $items
 */
function crearChecklistProceso(Proceso $proceso, array $items): ChecklistDocumentalProceso
{
    $conjunto = ConjuntoRequisitosDocumentales::firstOrCreate(
        ['codigo' => 'pago_proveedores'],
        ['nombre' => 'Requisitos documentales de Pago de Proveedores', 'activo' => true],
    );

    $checklist = ChecklistDocumentalProceso::create([
        'proceso_id' => $proceso->id,
        'conjunto_requisitos_documentales_id' => $conjunto->id,
        'generado_en' => now(),
    ]);

    foreach ($items as $item) {
        agregarItemChecklist($checklist, $proceso, $item['codigo'], $item['tipo_requisito']);
    }

    return $checklist;
}

function agregarItemChecklist(
    ChecklistDocumentalProceso $checklist,
    Proceso $proceso,
    string $codigoTipo,
    string $tipoRequisito,
): void {
    $tipo = TipoDocumento::where('codigo', $codigoTipo)->firstOrFail();

    $requisito = RequisitoDocumental::create([
        'conjunto_requisitos_documentales_id' => $checklist->conjunto_requisitos_documentales_id,
        'tipo_documento_id' => $tipo->id,
        'modalidad_id' => null,
        'tipo_proceso_pago_id' => null,
        'definicion_workflow_id' => $proceso->definicion_workflow_id,
        'tipo_requisito' => $tipoRequisito,
        'activo' => true,
    ]);

    $checklist->items()->create([
        'requisito_documental_id' => $requisito->id,
        'tipo_documento_id' => $tipo->id,
        'tipo_requisito' => $tipoRequisito,
        'documento_id' => null,
        'estado_cumplimiento' => 'pendiente',
    ]);
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

test('un Administrador Zonal no puede devolver un pago que todavía está en instancia Finanzas', function () {
    $e = crearEscenarioRevision();
    $zonal = usuarioConRol('administrador_zonal', $e['cfinancieroId']);

    // El Gate de RevisionTransicionPagoController solo exige "revisar" (alguna
    // de las dos instancias); el Zonal con la misma jurisdicción lo pasa aunque
    // el caso siga en en_revision_finanzas. observar_finanzas SHALL exigir su
    // propio permiso (revisar_finanzas) para que este intento sea rechazado.
    $response = $this->actingAs($zonal)->post(
        route('pago-proveedores.revision.pagos.transicion', ['egresoCgu' => $e['egreso']->id, 'caso' => $e['caso']->id]),
        ['accion' => 'devolver', 'comentario' => 'Intento fuera de instancia.'],
    );

    $response->assertRedirect();
    expect($e['caso']->proceso->refresh()->estadoActual->codigo)->toBe('en_revision_finanzas');
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

test('aprobar desde Finanzas es rechazado si el caso no tiene centro financiero determinable y no hay default configurado', function () {
    config(['pago-proveedores.cfinanciero_default_codigo' => null]);

    $e = crearEscenarioRevision();
    $e['caso']->update(['proceso_adquisicion_id' => null]);

    $revision = app(RevisionEgresoService::class);
    $validaciones = app(ValidacionDocumentoInstanciaService::class);
    $finanzas = usuarioConRol('jefe_finanzas');

    $validaciones->validar($e['documento'], InstanciaRevision::Finanzas, 'valido', null, $finanzas);
    $revision->verificarTotales($e['caso'], InstanciaRevision::Finanzas, $finanzas);

    expect(fn () => $revision->aprobarPago($e['caso']->refresh(), $finanzas))
        ->toThrow(RuntimeException::class);

    expect($e['caso']->proceso->refresh()->estadoActual->codigo)->toBe('en_revision_finanzas');
});

test('aprobar desde Finanzas sin adquisición vinculada usa el cfinanciero por defecto y no bloquea', function () {
    $e = crearEscenarioRevision();
    config(['pago-proveedores.cfinanciero_default_codigo' => $e['egreso']->cfinanciero->codigo]);
    $e['caso']->update(['proceso_adquisicion_id' => null]);
    $e['egreso']->update(['cfinanciero_id' => null]);

    $revision = app(RevisionEgresoService::class);
    $validaciones = app(ValidacionDocumentoInstanciaService::class);
    $finanzas = usuarioConRol('jefe_finanzas');

    $validaciones->validar($e['documento'], InstanciaRevision::Finanzas, 'valido', null, $finanzas);
    $revision->verificarTotales($e['caso'], InstanciaRevision::Finanzas, $finanzas);
    $revision->aprobarPago($e['caso']->refresh(), $finanzas);

    expect($e['caso']->proceso->refresh()->estadoActual->codigo)->toBe('en_revision_zonal');
    expect($e['egreso']->refresh()->cfinanciero_id)->toBe($e['cfinancieroId']);
});

test('la instancia Finanzas puede devolver (observar) el pago con comentario vía HTTP', function () {
    $e = crearEscenarioRevision();
    $finanzas = usuarioConRol('jefe_finanzas');

    $response = $this->actingAs($finanzas)->post(
        route('pago-proveedores.revision.pagos.transicion', ['egresoCgu' => $e['egreso']->id, 'caso' => $e['caso']->id]),
        ['accion' => 'devolver', 'comentario' => 'Falta corregir el monto de la factura.'],
    );

    $response->assertSessionHasNoErrors();
    expect($e['caso']->proceso->refresh()->estadoActual->codigo)->toBe('observada');
});

test('un obligatorio del checklist presente y aprobado habilita la aprobación y se clasifica como obligatorio', function () {
    $e = crearEscenarioRevision();
    $revision = app(RevisionEgresoService::class);
    $validaciones = app(ValidacionDocumentoInstanciaService::class);
    $presenter = app(RevisionEgresoPresenter::class);
    $finanzas = usuarioConRol('jefe_finanzas');

    $validaciones->validar($e['documento'], InstanciaRevision::Finanzas, 'valido', null, $finanzas);
    $revision->verificarTotales($e['caso'], InstanciaRevision::Finanzas, $finanzas);

    expect($revision->pagoListoParaAprobar($e['caso']->refresh()))->toBeTrue();

    $pago = collect($presenter->detalle($e['egreso']->refresh(), $finanzas)['pagos'])
        ->firstWhere('sgf_id', $e['caso']->sgf_id);
    $factura = collect($pago['documentos'])->firstWhere('tipo', 'Factura');

    expect($factura['clasificacion'])->toBe('obligatorio');
    expect($pago['faltantes'])->toBeEmpty();
    expect($pago['obligatorios_ok'])->toBe(1);
    expect($pago['obligatorios_total'])->toBe(1);
});

test('un obligatorio del checklist sin documento vinculado aparece como faltante y bloquea la aprobación', function () {
    $e = crearEscenarioRevision();
    $revision = app(RevisionEgresoService::class);
    $validaciones = app(ValidacionDocumentoInstanciaService::class);
    $presenter = app(RevisionEgresoPresenter::class);
    $finanzas = usuarioConRol('jefe_finanzas');

    // COMPROBANTE obligatorio en el checklist, sin documento vinculado -> faltante.
    agregarItemChecklist($e['checklist'], $e['caso']->proceso, 'COMPROBANTE', 'obligatorio');

    // El obligatorio presente (FACTURA) sí se aprueba y los totales se verifican;
    // aun así el faltante bloquea.
    $validaciones->validar($e['documento'], InstanciaRevision::Finanzas, 'valido', null, $finanzas);
    $revision->verificarTotales($e['caso'], InstanciaRevision::Finanzas, $finanzas);

    expect($revision->pagoListoParaAprobar($e['caso']->refresh()))->toBeFalse();

    $pago = collect($presenter->detalle($e['egreso']->refresh(), $finanzas)['pagos'])
        ->firstWhere('sgf_id', $e['caso']->sgf_id);

    expect($pago['faltantes'])->toHaveCount(1);
    expect($pago['obligatorios_ok'])->toBe(1);
    expect($pago['obligatorios_total'])->toBe(2);
});

test('un documento opcional pendiente no bloquea la aprobación y se clasifica como opcional', function () {
    $e = crearEscenarioRevision();
    $revision = app(RevisionEgresoService::class);
    $validaciones = app(ValidacionDocumentoInstanciaService::class);
    $presenter = app(RevisionEgresoPresenter::class);
    $finanzas = usuarioConRol('jefe_finanzas');

    // Documento de un tipo que NO es obligatorio en el checklist -> opcional.
    $tipoOpcional = TipoDocumento::where('codigo', 'ACTA_RECEP')->firstOrFail();
    $docOpcional = Documento::create(['tipo_documento_id' => $tipoOpcional->id, 'titulo' => 'acta.pdf']);
    $e['caso']->proceso->vinculosDocumento()->create(['documento_id' => $docOpcional->id, 'activo' => true]);

    // Se aprueba solo el obligatorio (FACTURA); el opcional queda pendiente.
    $validaciones->validar($e['documento'], InstanciaRevision::Finanzas, 'valido', null, $finanzas);
    $revision->verificarTotales($e['caso'], InstanciaRevision::Finanzas, $finanzas);

    expect($revision->pagoListoParaAprobar($e['caso']->refresh()))->toBeTrue();

    $pago = collect($presenter->detalle($e['egreso']->refresh(), $finanzas)['pagos'])
        ->firstWhere('sgf_id', $e['caso']->sgf_id);
    $opcional = collect($pago['documentos'])->firstWhere('titulo', 'acta.pdf');

    expect($opcional['clasificacion'])->toBe('opcional');
    expect($pago['obligatorios_total'])->toBe(1);
    expect($pago['obligatorios_ok'])->toBe(1);
});

test('el gating por obligatorios es independiente en Finanzas y Zonal', function () {
    $e = crearEscenarioRevision();
    $revision = app(RevisionEgresoService::class);
    $validaciones = app(ValidacionDocumentoInstanciaService::class);
    $finanzas = usuarioConRol('jefe_finanzas');
    $zonal = usuarioConRol('administrador_zonal', $e['cfinancieroId']);

    $validaciones->validar($e['documento'], InstanciaRevision::Finanzas, 'valido', null, $finanzas);
    $revision->verificarTotales($e['caso'], InstanciaRevision::Finanzas, $finanzas);
    $revision->aprobarPago($e['caso']->refresh(), $finanzas);

    $caso = $e['caso']->refresh();

    // El obligatorio aprobado por Finanzas vuelve a estar pendiente para Zonal.
    expect($revision->pagoListoParaAprobar($caso))->toBeFalse();

    $validaciones->validar($e['documento'], InstanciaRevision::Zonal, 'valido', null, $zonal);
    $revision->verificarTotales($caso, InstanciaRevision::Zonal, $zonal);

    expect($revision->pagoListoParaAprobar($caso->refresh()))->toBeTrue();
});

test('un proceso sin checklist deja los documentos como opcionales y no habilita la aprobación', function () {
    $e = crearEscenarioRevision();
    $revision = app(RevisionEgresoService::class);
    $validaciones = app(ValidacionDocumentoInstanciaService::class);
    $finanzas = usuarioConRol('jefe_finanzas');

    // Elimina el checklist generado por el escenario.
    $e['checklist']->items()->delete();
    $e['checklist']->delete();

    $caso = CasoPagoProveedor::findOrFail($e['caso']->id);
    $clasificados = $validaciones->documentosDelCaso($caso);

    expect($clasificados['obligatorios'])->toBeEmpty();
    expect($clasificados['faltantes'])->toBeEmpty();
    expect($clasificados['opcionales'])->toHaveCount(1);

    $validaciones->validar($e['documento'], InstanciaRevision::Finanzas, 'valido', null, $finanzas);
    $revision->verificarTotales($e['caso'], InstanciaRevision::Finanzas, $finanzas);

    expect($revision->pagoListoParaAprobar($caso->refresh()))->toBeFalse();
});
