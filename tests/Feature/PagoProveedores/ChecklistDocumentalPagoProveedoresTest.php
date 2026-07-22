<?php

use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\Documento;
use App\Models\TipoDocumento;
use App\Models\TipoProcesoPago;
use App\Models\User;
use App\Services\PagoProveedores\CasoPagoProveedorImporter;
use Database\Seeders\RequisitosDocumentalesPagoProveedoresSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TiposDocumentoSeeder;
use Database\Seeders\TiposProcesoPagoSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function sembrarRequisitosDocumentalesPagoProveedores(): void
{
    test()->seed(WorkflowPagoProveedoresSeeder::class);
    test()->seed(TiposDocumentoSeeder::class);
    test()->seed(TiposProcesoPagoSeeder::class);
    test()->seed(RequisitosDocumentalesPagoProveedoresSeeder::class);
}

test('el seeder crea el conjunto de requisitos documentales de pago de proveedores con factura obligatoria', function () {
    sembrarRequisitosDocumentalesPagoProveedores();

    $conjunto = ConjuntoRequisitosDocumentales::where('codigo', 'pago_proveedores')->first();
    expect($conjunto)->not->toBeNull();

    $factura = TipoDocumento::where('codigo', 'FACTURA')->first();
    $requisitoFactura = $conjunto->requisitos()->where('tipo_documento_id', $factura->id)->first();

    expect($requisitoFactura)->not->toBeNull();
    expect($requisitoFactura->tipo_requisito)->toBe('obligatorio');
});

test('abrir el detalle de un caso de pago genera un checklist con factura', function () {
    $this->withoutVite();
    sembrarRequisitosDocumentalesPagoProveedores();

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgfParaApi(['sgf_id' => 'caso-checklist-1']));

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.casos.show', $caso));

    $response->assertOk();
    $response->assertInertia(function (Assert $page) {
        $page->component('pago-proveedores/casos/show', shouldExist: false);
        $items = $page->toArray()['props']['caso']['proceso']['checklist']['items'];
        $tiposDocumento = array_column($items, 'tipo_documento');

        expect($tiposDocumento)->toContain('Factura');
    });
});

test('el checklist expone el tipo_documento_id de cada ítem', function () {
    $this->withoutVite();
    sembrarRequisitosDocumentalesPagoProveedores();

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgfParaApi(['sgf_id' => 'caso-checklist-tipo-id']));

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.casos.show', $caso));

    $response->assertOk();
    $response->assertInertia(function (Assert $page) {
        $page->component('pago-proveedores/casos/show', shouldExist: false);
        $items = $page->toArray()['props']['caso']['proceso']['checklist']['items'];
        $factura = TipoDocumento::where('codigo', 'FACTURA')->firstOrFail();
        $itemFactura = collect($items)->firstWhere('tipo_documento', 'Factura');

        expect($itemFactura)->not->toBeNull();
        expect($itemFactura['tipo_documento_id'])->toBe($factura->id);
    });
});

test('el detalle del caso incluye los tipos de proceso de pago disponibles y el clasificado en el proceso', function () {
    $this->withoutVite();
    sembrarRequisitosDocumentalesPagoProveedores();

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgfParaApi(['sgf_id' => 'caso-tipo-proceso-pago-1']));
    $tipoContrato = TipoProcesoPago::where('codigo', 'CONTRATO')->firstOrFail();
    $caso->proceso->update(['tipo_proceso_pago_id' => $tipoContrato->id]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.casos.show', $caso));

    $response->assertOk();
    $response->assertInertia(function (Assert $page) use ($tipoContrato) {
        $page->component('pago-proveedores/casos/show', shouldExist: false);
        $props = $page->toArray()['props'];

        $codigos = array_column($props['tiposProcesoPago'], 'codigo');
        expect($codigos)->toContain('COMPRA', 'CONTRATO', 'CONVENIO', 'REEMBOLSO', 'ANTICIPO', 'OTRO');

        expect($props['caso']['proceso']['tipo_proceso_pago_id'])->toBe($tipoContrato->id);
        expect($props['caso']['proceso']['tipo_proceso_pago']['codigo'])->toBe('CONTRATO');
    });
});

test('subir un documento usando el tipo_documento_id de un ítem pendiente del checklist lo marca como cargado', function () {
    $this->withoutVite();
    Storage::fake('local');
    $this->seed(RolesAndPermissionsSeeder::class);
    sembrarRequisitosDocumentalesPagoProveedores();

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgfParaApi(['sgf_id' => 'caso-checklist-subida-directa']));

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('documentos.gestionar');

    $primeraCarga = $this->actingAs($usuario)->get(route('pago-proveedores.casos.show', $caso));
    $items = $primeraCarga->viewData('page')['props']['caso']['proceso']['checklist']['items'];
    $itemFactura = collect($items)->firstWhere('tipo_documento', 'Factura');

    expect($itemFactura['estado_cumplimiento'])->toBe('pendiente');

    $archivo = UploadedFile::fake()->create('factura.pdf', 100, 'application/pdf');

    $response = $this->actingAs($usuario)->post(
        route('procesos.documentos.store', $caso->proceso),
        ['archivo' => $archivo, 'tipo_documento_id' => $itemFactura['tipo_documento_id']],
    );

    $response->assertSessionHasNoErrors();

    $segundaCarga = $this->actingAs($usuario)->get(route('pago-proveedores.casos.show', $caso));
    $itemsActualizados = $segundaCarga->viewData('page')['props']['caso']['proceso']['checklist']['items'];
    $itemFacturaActualizado = collect($itemsActualizados)->firstWhere('tipo_documento', 'Factura');

    expect($itemFacturaActualizado['estado_cumplimiento'])->toBe('cargado');
    expect($itemFacturaActualizado['documento_id'])->not->toBeNull();
});

test('un documento vinculado que no coincide con ningún ítem del checklist se expone como no coincidente', function () {
    $this->withoutVite();
    sembrarRequisitosDocumentalesPagoProveedores();

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgfParaApi(['sgf_id' => 'caso-huerfano-1']));

    $tipoOtro = TipoDocumento::firstOrCreate(['codigo' => 'OTRO'], ['nombre' => 'Otro', 'activo' => true]);
    $documento = Documento::create(['tipo_documento_id' => $tipoOtro->id, 'titulo' => 'huerfano.pdf']);
    $caso->proceso->vinculosDocumento()->create(['documento_id' => $documento->id, 'activo' => true]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.casos.show', $caso));

    $response->assertOk();
    $response->assertInertia(function (Assert $page) use ($documento) {
        $page->component('pago-proveedores/casos/show', shouldExist: false);
        $documentos = $page->toArray()['props']['caso']['proceso']['documentos'];
        $docHuerfano = collect($documentos)->firstWhere('documento_id', $documento->id);

        expect($docHuerfano)->not->toBeNull();
        expect($docHuerfano['coincide_checklist'])->toBeFalse();
    });
});

test('el checklist expone el nombre del archivo por ítem, los documentos re-vinculables y el N° DTE (caso.numero)', function () {
    $this->withoutVite();
    sembrarRequisitosDocumentalesPagoProveedores();

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(
        crearSnapshotSgfParaApi(['sgf_id' => 'caso-nombre-archivo', 'numero' => '293819']),
    );

    // Documento FACTURA vinculado (activo) con nombre de archivo real → el ítem
    // obligatorio "Factura" del checklist queda cargado con ese documento.
    $factura = TipoDocumento::where('codigo', 'FACTURA')->firstOrFail();
    $docFactura = Documento::create(['tipo_documento_id' => $factura->id, 'titulo' => 'FAE-293819.pdf']);
    $docFactura->versiones()->create(['numero_version' => 1, 'ruta_archivo' => 'documentos/fae.pdf', 'nombre_archivo' => 'FAE-293819.pdf']);
    $caso->proceso->vinculosDocumento()->create(['documento_id' => $docFactura->id, 'activo' => true]);

    // Documento desvinculado (activo=false) → re-vinculable.
    $tipoOtro = TipoDocumento::firstOrCreate(['codigo' => 'OTRO'], ['nombre' => 'Otro', 'activo' => true]);
    $docDesvinculado = Documento::create(['tipo_documento_id' => $tipoOtro->id, 'titulo' => 'viejo.pdf']);
    $docDesvinculado->versiones()->create(['numero_version' => 1, 'ruta_archivo' => 'documentos/viejo.pdf', 'nombre_archivo' => 'viejo.pdf']);
    $caso->proceso->vinculosDocumento()->create(['documento_id' => $docDesvinculado->id, 'activo' => false]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.casos.show', $caso));

    $response->assertOk();
    $response->assertInertia(function (Assert $page) {
        $props = $page->toArray()['props'];
        $items = $props['caso']['proceso']['checklist']['items'];

        $itemFactura = collect($items)->firstWhere('tipo_documento', 'Factura');
        expect($itemFactura['nombre_archivo'])->toBe('FAE-293819.pdf');

        // Un ítem obligatorio sin documento (p. ej. Comprobante) no trae nombre.
        $itemPendiente = collect($items)->first(fn ($i) => $i['documento_id'] === null);
        expect($itemPendiente['nombre_archivo'])->toBeNull();

        $revinculables = collect($props['caso']['proceso']['documentos_revinculables']);
        expect($revinculables->pluck('nombre_archivo'))->toContain('viejo.pdf');

        // El N° DTE de la cabecera se sirve como caso.numero.
        expect($props['caso']['numero'])->toBe('293819');
    });
});

test('abrir el detalle de un caso de pago dos veces no duplica los items del checklist', function () {
    $this->withoutVite();
    sembrarRequisitosDocumentalesPagoProveedores();

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgfParaApi(['sgf_id' => 'caso-checklist-2']));

    $usuario = User::factory()->create();

    $this->actingAs($usuario)->get(route('pago-proveedores.casos.show', $caso));
    $cantidadItemsPrimeraCarga = $caso->proceso->checklist->items()->count();

    $this->actingAs($usuario)->get(route('pago-proveedores.casos.show', $caso));
    $cantidadItemsSegundaCarga = $caso->proceso->checklist->items()->count();

    expect($cantidadItemsPrimeraCarga)->toBeGreaterThan(0);
    expect($cantidadItemsSegundaCarga)->toBe($cantidadItemsPrimeraCarga);
});
