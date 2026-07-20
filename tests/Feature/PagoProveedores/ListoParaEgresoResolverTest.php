<?php

use App\Models\CasoPagoProveedor;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\PagoProveedores\ListoParaEgresoResolver;

test('un caso null no está listo', function () {
    expect(app(ListoParaEgresoResolver::class)->resuelve(null))->toBeFalse();
});

test('los 4 criterios completos con checklist de cero obligatorios están listos', function () {
    // crearCasoBaseParaPresenter/crearTipoProcesoPagoSinObligatorios/resolverChecklistDe
    // están definidas en PreparacionEgresoPresenterTest.php (mismo directorio,
    // convención ya usada en este repo para compartir helpers entre tests hermanos —
    // ver crearCasoPagoProveedorDePrueba en AccesoDirectoCrearEgresoDesdeDetalleCasoTest.php).
    $proveedor = Proveedor::create(['rutproveedor' => '22222222-2', 'nombre' => 'Proveedor Resolver SPA', 'activo' => true]);
    $caso = crearCasoBaseParaPresenter('sgf-resolver-sin-obligatorios', $proveedor->id);
    $tipo = crearTipoProcesoPagoSinObligatorios('RESOLVER_SIN_OBLIGATORIOS');
    $caso->proceso->update(['tipo_proceso_pago_id' => $tipo->id]);
    $caso->update(['sgf_numero_traspaso' => 'TR-RESOLVER-1']);
    resolverChecklistDe($caso);

    expect(app(ListoParaEgresoResolver::class)->resuelve($caso->fresh()))->toBeTrue();
});

/**
 * El checklist_documental_proceso se resuelve/persiste al abrir el
 * detalle del caso (CasoPagoProveedorController::cargarDetalle()) —
 * crearCasoListoParaEgresoDePrueba() por sí sola no lo genera, solo deja
 * los vínculos de documento listos para cuando se resuelva.
 */
function resolverChecklistVisitandoDetalle(CasoPagoProveedor $caso): void
{
    test()->actingAs(User::factory()->create())
        ->get(route('pago-proveedores.casos.show', $caso))
        ->assertOk();
}

test('los 4 criterios completos con checklist de N obligatorios satisfechos están listos', function () {
    // crearCasoListoParaEgresoDePrueba está definida en
    // AccesoDirectoCrearEgresoDesdeDetalleCasoTest.php (mismo directorio).
    $caso = crearCasoListoParaEgresoDePrueba('sgf-resolver-completo');
    resolverChecklistVisitandoDetalle($caso);

    expect(app(ListoParaEgresoResolver::class)->resuelve(CasoPagoProveedor::find($caso->id)))->toBeTrue();
});

test('falta el tipo de proceso: no está listo', function () {
    $caso = crearCasoListoParaEgresoDePrueba('sgf-resolver-sin-tipo');
    $caso->proceso->update(['tipo_proceso_pago_id' => null]);
    resolverChecklistVisitandoDetalle($caso);

    expect(app(ListoParaEgresoResolver::class)->resuelve(CasoPagoProveedor::find($caso->id)))->toBeFalse();
});

test('falta el traspaso: no está listo', function () {
    $caso = crearCasoListoParaEgresoDePrueba('sgf-resolver-sin-traspaso');
    $caso->registrosContablesCgu()->delete();
    resolverChecklistVisitandoDetalle($caso);

    expect(app(ListoParaEgresoResolver::class)->resuelve(CasoPagoProveedor::find($caso->id)))->toBeFalse();
});

test('falta el proveedor: no está listo', function () {
    $caso = crearCasoListoParaEgresoDePrueba('sgf-resolver-sin-proveedor');
    $caso->update(['proveedor_id' => null]);
    resolverChecklistVisitandoDetalle($caso);

    expect(app(ListoParaEgresoResolver::class)->resuelve(CasoPagoProveedor::find($caso->id)))->toBeFalse();
});

test('falta un documento obligatorio del checklist: no está listo', function () {
    $caso = crearCasoListoParaEgresoDePrueba('sgf-resolver-checklist-incompleto');
    $caso->proceso->vinculosDocumento()->first()->update(['activo' => false]);
    resolverChecklistVisitandoDetalle($caso);

    expect(app(ListoParaEgresoResolver::class)->resuelve(CasoPagoProveedor::find($caso->id)))->toBeFalse();
});
