<?php

use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\TipoDocumento;
use App\Models\User;
use App\Services\PagoProveedores\CasoPagoProveedorImporter;
use Database\Seeders\RequisitosDocumentalesPagoProveedoresSeeder;
use Database\Seeders\TiposDocumentoSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function sembrarRequisitosDocumentalesPagoProveedores(): void
{
    test()->seed(WorkflowPagoProveedoresSeeder::class);
    test()->seed(TiposDocumentoSeeder::class);
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

test('abrir el detalle de un caso de pago dos veces no duplica los items del checklist', function () {
    $this->withoutVite();
    sembrarRequisitosDocumentalesPagoProveedores();

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot(crearSnapshotSgfParaApi(['sgf_id' => 'caso-checklist-2']));

    $usuario = User::factory()->create();

    $this->actingAs($usuario)->get(route('pago-proveedores.casos.show', $caso));
    $this->actingAs($usuario)->get(route('pago-proveedores.casos.show', $caso));

    $conjunto = ConjuntoRequisitosDocumentales::where('codigo', 'pago_proveedores')->first();
    $cantidadItems = $caso->proceso->checklist->items()->count();
    $cantidadRequisitosEsperados = $conjunto->requisitos()->count();

    expect($cantidadItems)->toBe($cantidadRequisitosEsperados);
});
