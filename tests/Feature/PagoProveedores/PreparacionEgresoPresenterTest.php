<?php

use App\Models\CasoPagoProveedor;
use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\DefinicionWorkflow;
use App\Models\Documento;
use App\Models\Proceso;
use App\Models\Proveedor;
use App\Models\RegistroContableCgu;
use App\Models\RequisitoDocumental;
use App\Models\TipoDocumento;
use App\Models\TipoProcesoPago;
use App\Services\Documentos\ResolutorChecklistDocumentalProceso;
use App\Services\PagoProveedores\PreparacionEgresoPresenter;
use Database\Seeders\RequisitosDocumentalesPagoProveedoresSeeder;
use Database\Seeders\TiposDocumentoSeeder;
use Database\Seeders\TiposProcesoPagoSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Illuminate\Support\Collection;

/**
 * Crea un caso_pago_proveedor con su Proceso, sin tipo de proceso
 * clasificado, sin traspaso, sin checklist resuelto y sin proveedor
 * identificado — el punto de partida para armar cada escenario desde cero.
 */
function crearCasoBaseParaPresenter(string $sgfId, ?int $proveedorId = null): CasoPagoProveedor
{
    test()->seed(WorkflowPagoProveedoresSeeder::class);
    test()->seed(TiposDocumentoSeeder::class);
    test()->seed(TiposProcesoPagoSeeder::class);
    test()->seed(RequisitosDocumentalesPagoProveedoresSeeder::class);

    $caso = CasoPagoProveedor::create([
        'sgf_id' => $sgfId,
        'proveedor_id' => $proveedorId,
        'rut_proveedor' => fake()->numerify('########-#'),
        'monto' => 100000,
        'sgf_status' => 'EN_TRAMITE',
    ]);

    $definicion = DefinicionWorkflow::where('codigo', 'pago_proveedores')->firstOrFail();
    Proceso::create([
        'definicion_workflow_id' => $definicion->id,
        'estado_actual_id' => $definicion->estados()->where('es_inicial', true)->value('id'),
        'sujeto_type' => CasoPagoProveedor::class,
        'sujeto_id' => $caso->id,
        'monto' => 100000,
    ]);

    return $caso->refresh();
}

/**
 * Crea un TipoProcesoPago cuyo checklist resuelve a cero ítems
 * obligatorios: marca FACTURA y COMPROBANTE (universales por defecto, ver
 * RequisitosDocumentalesPagoProveedoresSeeder::REGLAS_UNIVERSALES) como
 * "opcional" específicamente para este tipo — la regla más específica gana
 * sobre la universal (ResolutorChecklistDocumentalProceso::especificidad())
 * — replicando el mecanismo real usado para el tipo "Remesa".
 */
function crearTipoProcesoPagoSinObligatorios(string $codigo): TipoProcesoPago
{
    $conjunto = ConjuntoRequisitosDocumentales::where('codigo', 'pago_proveedores')->firstOrFail();
    $definicion = DefinicionWorkflow::where('codigo', 'pago_proveedores')->firstOrFail();
    $tipo = TipoProcesoPago::create(['codigo' => $codigo, 'nombre' => $codigo, 'activo' => true]);

    foreach (['FACTURA', 'COMPROBANTE'] as $codigoDocumento) {
        RequisitoDocumental::create([
            'conjunto_requisitos_documentales_id' => $conjunto->id,
            'tipo_documento_id' => TipoDocumento::where('codigo', $codigoDocumento)->value('id'),
            'definicion_workflow_id' => $definicion->id,
            'tipo_proceso_pago_id' => $tipo->id,
            'tipo_requisito' => 'opcional',
            'activo' => true,
        ]);
    }

    return $tipo;
}

/**
 * Crea un TipoProcesoPago con `requiere_traspaso_cgu = false` — replica el
 * mecanismo real usado para el tipo "Remesa" (ver migración
 * add_requiere_traspaso_cgu_to_tipos_proceso_pago_table).
 */
function crearTipoProcesoPagoSinTraspaso(string $codigo): TipoProcesoPago
{
    return TipoProcesoPago::create([
        'codigo' => $codigo,
        'nombre' => $codigo,
        'activo' => true,
        'requiere_traspaso_cgu' => false,
    ]);
}

function resolverChecklistDe(CasoPagoProveedor $caso): void
{
    $conjunto = ConjuntoRequisitosDocumentales::where('codigo', 'pago_proveedores')->firstOrFail();
    app(ResolutorChecklistDocumentalProceso::class)->resolve($caso->proceso, $conjunto);
}

function criteriosDe(CasoPagoProveedor $caso): Collection
{
    $caso->refresh()->load([
        'proveedor',
        'registrosContablesCgu',
        'proceso.checklist.items',
        'proceso.tipoProcesoPago',
    ]);

    return collect(app(PreparacionEgresoPresenter::class)->criterios($caso))->keyBy('criterio');
}

test('checklist nunca resuelto: no cumplido, con detalle "Sin checklist generado"', function () {
    $caso = crearCasoBaseParaPresenter('sgf-presenter-null');

    $criterios = criteriosDe($caso);

    expect($criterios['checklist_documental']['cumplido'])->toBeFalse();
    expect($criterios['checklist_documental']['detalle'])->toBe('Sin checklist generado');
});

test('checklist resuelto sin ítems obligatorios (con un opcional): cumplido, con detalle "Sin ítems obligatorios"', function () {
    $caso = crearCasoBaseParaPresenter('sgf-presenter-sin-obligatorios');
    $tipo = crearTipoProcesoPagoSinObligatorios('SIN_OBLIGATORIOS_TEST');
    $caso->proceso->update(['tipo_proceso_pago_id' => $tipo->id]);
    resolverChecklistDe($caso);

    $criterios = criteriosDe($caso);

    expect($criterios['checklist_documental']['cumplido'])->toBeTrue();
    expect($criterios['checklist_documental']['detalle'])->toBe('Sin ítems obligatorios');
});

test('checklist resuelto con obligatorios pendientes: no cumplido, con conteo parcial', function () {
    $caso = crearCasoBaseParaPresenter('sgf-presenter-parcial');
    $tipo = TipoProcesoPago::where('codigo', 'ANTICIPO')->firstOrFail();
    $caso->proceso->update(['tipo_proceso_pago_id' => $tipo->id]);
    resolverChecklistDe($caso);

    // ANTICIPO resuelve a FACTURA + COMPROBANTE (universales) + RESOLUCION
    // (matriz), los 3 obligatorios — se deja solo FACTURA con documento.
    $tipoDocumento = TipoDocumento::where('codigo', 'FACTURA')->firstOrFail();
    $documento = Documento::create(['tipo_documento_id' => $tipoDocumento->id, 'titulo' => 'FACTURA.pdf']);
    $caso->proceso->vinculosDocumento()->create(['documento_id' => $documento->id, 'activo' => true]);
    resolverChecklistDe($caso);

    $criterios = criteriosDe($caso);

    expect($criterios['checklist_documental']['cumplido'])->toBeFalse();
    expect($criterios['checklist_documental']['detalle'])->toBe('1 / 3 obligatorios');
});

test('checklist resuelto con todos los obligatorios satisfechos: cumplido, con conteo completo', function () {
    $caso = crearCasoBaseParaPresenter('sgf-presenter-completo');
    $tipo = TipoProcesoPago::where('codigo', 'ANTICIPO')->firstOrFail();
    $caso->proceso->update(['tipo_proceso_pago_id' => $tipo->id]);

    foreach (['FACTURA', 'COMPROBANTE', 'RESOLUCION'] as $codigo) {
        $tipoDocumento = TipoDocumento::where('codigo', $codigo)->firstOrFail();
        $documento = Documento::create(['tipo_documento_id' => $tipoDocumento->id, 'titulo' => "{$codigo}.pdf"]);
        $caso->proceso->vinculosDocumento()->create(['documento_id' => $documento->id, 'activo' => true]);
    }
    resolverChecklistDe($caso);

    $criterios = criteriosDe($caso);

    expect($criterios['checklist_documental']['cumplido'])->toBeTrue();
    expect($criterios['checklist_documental']['detalle'])->toBe('3 / 3 obligatorios');
});

test('traspaso vía registro contable manual cumple el criterio', function () {
    $caso = crearCasoBaseParaPresenter('sgf-presenter-traspaso-manual');
    RegistroContableCgu::create([
        'caso_pago_proveedor_id' => $caso->id,
        'numero_registro' => 'RC-1',
        'fecha_registro' => now(),
        'monto' => 100000,
    ]);

    $criterios = criteriosDe($caso);

    expect($criterios['traspaso_cgu']['cumplido'])->toBeTrue();
    expect($criterios['traspaso_cgu']['detalle'])->toBe('RC-1');
});

test('traspaso vía sgf_numero_traspaso sin registro manual cumple el criterio', function () {
    $caso = crearCasoBaseParaPresenter('sgf-presenter-traspaso-sgf');
    $caso->update(['sgf_numero_traspaso' => 'TR-9']);

    $criterios = criteriosDe($caso);

    expect($criterios['traspaso_cgu']['cumplido'])->toBeTrue();
    expect($criterios['traspaso_cgu']['detalle'])->toBe('TR-9');
});

test('sin traspaso de ningún tipo no cumple el criterio', function () {
    $caso = crearCasoBaseParaPresenter('sgf-presenter-sin-traspaso');

    $criterios = criteriosDe($caso);

    expect($criterios['traspaso_cgu']['cumplido'])->toBeFalse();
    expect($criterios['traspaso_cgu']['detalle'])->toBe('Sin registrar');
});

test('un tipo de proceso que no requiere traspaso cumple el criterio sin ningún registro', function () {
    $caso = crearCasoBaseParaPresenter('sgf-presenter-sin-requerir-traspaso');
    $tipo = crearTipoProcesoPagoSinTraspaso('SIN_TRASPASO_TEST');
    $caso->proceso->update(['tipo_proceso_pago_id' => $tipo->id]);

    $criterios = criteriosDe($caso);

    expect($criterios['traspaso_cgu']['cumplido'])->toBeTrue();
    expect($criterios['traspaso_cgu']['detalle'])->toBe('No requiere traspaso');
});

test('un caso sin tipo de proceso clasificado sigue exigiendo traspaso por defecto', function () {
    $caso = crearCasoBaseParaPresenter('sgf-presenter-sin-clasificar-traspaso');

    $criterios = criteriosDe($caso);

    expect($caso->proceso->tipo_proceso_pago_id)->toBeNull();
    expect($criterios['traspaso_cgu']['cumplido'])->toBeFalse();
    expect($criterios['traspaso_cgu']['detalle'])->toBe('Sin registrar');
});

test('tipo de proceso sin clasificar no cumple el criterio', function () {
    $caso = crearCasoBaseParaPresenter('sgf-presenter-sin-tipo');

    $criterios = criteriosDe($caso);

    expect($criterios['tipo_proceso']['cumplido'])->toBeFalse();
    expect($criterios['tipo_proceso']['detalle'])->toBe('Sin clasificar');
});

test('tipo de proceso clasificado cumple el criterio con su nombre', function () {
    $caso = crearCasoBaseParaPresenter('sgf-presenter-con-tipo');
    $tipo = TipoProcesoPago::where('codigo', 'ANTICIPO')->firstOrFail();
    $caso->proceso->update(['tipo_proceso_pago_id' => $tipo->id]);

    $criterios = criteriosDe($caso);

    expect($criterios['tipo_proceso']['cumplido'])->toBeTrue();
    expect($criterios['tipo_proceso']['detalle'])->toBe($tipo->nombre);
});

test('proveedor no identificado no cumple el criterio', function () {
    $caso = crearCasoBaseParaPresenter('sgf-presenter-sin-proveedor');

    $criterios = criteriosDe($caso);

    expect($criterios['proveedor']['cumplido'])->toBeFalse();
    expect($criterios['proveedor']['detalle'])->toBe('No identificado');
});

test('proveedor identificado cumple el criterio con su nombre', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '11111111-1', 'nombre' => 'Proveedor Presenter SPA', 'activo' => true]);
    $caso = crearCasoBaseParaPresenter('sgf-presenter-con-proveedor', $proveedor->id);

    $criterios = criteriosDe($caso);

    expect($criterios['proveedor']['cumplido'])->toBeTrue();
    expect($criterios['proveedor']['detalle'])->toBe('Proveedor Presenter SPA');
});
