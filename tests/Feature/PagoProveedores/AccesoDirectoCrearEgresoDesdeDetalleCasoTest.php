<?php

use App\Models\CasoPagoProveedor;
use App\Models\DefinicionWorkflow;
use App\Models\Documento;
use App\Models\EgresoCgu;
use App\Models\Proceso;
use App\Models\Proveedor;
use App\Models\TipoDocumento;
use App\Models\TipoProcesoPago;
use App\Models\User;
use App\Services\PagoProveedores\ListoParaEgresoResolver;
use Database\Seeders\RequisitosDocumentalesPagoProveedoresSeeder;
use Database\Seeders\TiposDocumentoSeeder;
use Database\Seeders\TiposProcesoPagoSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Crea un caso en `importada_desde_sgf`, sin Egreso CGU, que cumple los 4
 * criterios del panel de preparación: tipo de proceso clasificado, un
 * registro contable CGU, checklist obligatorio con documento vinculado, y
 * proveedor identificado.
 */
function crearCasoListoParaEgresoDePrueba(string $sgfId): CasoPagoProveedor
{
    test()->seed(WorkflowPagoProveedoresSeeder::class);
    test()->seed(TiposDocumentoSeeder::class);
    test()->seed(TiposProcesoPagoSeeder::class);
    test()->seed(RequisitosDocumentalesPagoProveedoresSeeder::class);

    $proveedor = Proveedor::create([
        'rutproveedor' => fake()->unique()->numerify('########-#'),
        'nombre' => "Proveedor {$sgfId}",
        'activo' => true,
    ]);

    $caso = CasoPagoProveedor::create([
        'sgf_id' => $sgfId,
        'proveedor_id' => $proveedor->id,
        'rut_proveedor' => $proveedor->rutproveedor,
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

    // ANTICIPO es el tipo con menos requisitos obligatorios adicionales en
    // la matriz (database/seeders/RequisitosDocumentalesPagoProveedoresSeeder.php):
    // solo suma RESOLUCION a los universales FACTURA y COMPROBANTE.
    $caso->proceso->update([
        'tipo_proceso_pago_id' => TipoProcesoPago::where('codigo', 'ANTICIPO')->value('id'),
    ]);
    $caso->registrosContablesCgu()->create([
        'numero_registro' => "RC-{$sgfId}",
        'fecha_registro' => now(),
        'monto' => 100000,
    ]);

    foreach (['FACTURA', 'COMPROBANTE', 'RESOLUCION'] as $codigoTipoDocumento) {
        $tipoDocumento = TipoDocumento::where('codigo', $codigoTipoDocumento)->firstOrFail();
        $documento = Documento::create([
            'tipo_documento_id' => $tipoDocumento->id,
            'titulo' => "{$codigoTipoDocumento} {$sgfId}.pdf",
        ]);
        $caso->proceso->vinculosDocumento()->create(['documento_id' => $documento->id, 'activo' => true]);
    }

    return $caso->refresh();
}

test('el detalle de un caso listo y sin egreso expone egresos_cgu vacío junto a los 4 criterios completos', function () {
    $caso = crearCasoListoParaEgresoDePrueba('sgf-cta-listo-1');
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.casos.show', $caso));

    $response->assertOk();
    $response->assertInertia(function (Assert $page) {
        $page->component('pago-proveedores/casos/show', shouldExist: false);
        $props = $page->toArray()['props']['caso'];

        expect($props['egresos_cgu'] ?? [])->toBe([]);
        expect($props['proceso']['tipo_proceso_pago_id'])->not->toBeNull();
        expect($props['registros_contables_cgu'])->not->toBeEmpty();
        expect($props['proveedor']['nombre'])->not->toBeNull();

        $obligatorios = collect($props['proceso']['checklist']['items'])
            ->where('tipo_requisito', 'obligatorio');

        expect($obligatorios)->not->toBeEmpty();
        expect($obligatorios->every(fn ($item) => $item['documento_id'] !== null))->toBeTrue();
    });
});

test('el detalle de un caso ya asociado a un egreso expone egresos_cgu no vacío', function () {
    $caso = crearCasoListoParaEgresoDePrueba('sgf-cta-con-egreso-1');

    $egreso = EgresoCgu::create([
        'numero_egreso' => 'EGR-CTA-001',
        'fecha' => now(),
        'monto_total' => $caso->monto,
    ]);
    $egreso->items()->create(['caso_pago_proveedor_id' => $caso->id, 'monto' => $caso->monto]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.casos.show', $caso));

    $response->assertOk();
    $response->assertInertia(function (Assert $page) {
        $page->component('pago-proveedores/casos/show', shouldExist: false);
        $props = $page->toArray()['props']['caso'];

        expect($props['egresos_cgu'] ?? [])->not->toBeEmpty();
    });
});

test('el formulario de crear egreso con caso_pago_proveedor_id preselecciona solo ese caso sin restringir la lista', function () {
    $casoObjetivo = crearCasoListoParaEgresoDePrueba('sgf-preseleccion-1');
    $otroCaso = crearCasoPagoProveedorDePrueba('sgf-preseleccion-otro');

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.registrar_egreso');

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.egresos-cgu.create', [
        'caso_pago_proveedor_id' => $casoObjetivo->id,
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('pago-proveedores/egresos-cgu/crear', shouldExist: false)
        ->where('casoPagoProveedorId', $casoObjetivo->id)
        ->where('casos', fn ($casos) => collect($casos)->pluck('id')->contains($casoObjetivo->id)
            && collect($casos)->pluck('id')->contains($otroCaso->id))
    );
});

test('el formulario de crear egreso sin caso_pago_proveedor_id expone la prop en null', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $caso = crearCasoPagoProveedorDePrueba('sgf-sin-preseleccion-1');

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.registrar_egreso');

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.egresos-cgu.create'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('pago-proveedores/egresos-cgu/crear', shouldExist: false)
        ->where('casoPagoProveedorId', null)
        ->where('casos', fn ($casos) => collect($casos)->pluck('id')->contains($caso->id))
    );
});

test('un caso con sgf_numero_traspaso y sin registro contable manual cumple el criterio de traspaso para egreso', function () {
    $caso = crearCasoListoParaEgresoDePrueba('sgf-traspaso-listo');
    $caso->registrosContablesCgu()->delete();
    $caso->update(['sgf_numero_traspaso' => 'TR-2026-0087']);

    // El checklist_documental_proceso se resuelve/persiste al abrir el detalle.
    $this->actingAs(User::factory()->create())
        ->get(route('pago-proveedores.casos.show', $caso))
        ->assertOk();

    $casoFresco = CasoPagoProveedor::find($caso->id);

    expect(app(ListoParaEgresoResolver::class)->resuelve($casoFresco))->toBeTrue();
});

test('un caso sin registro contable manual ni sgf_numero_traspaso no cumple el criterio de traspaso', function () {
    $caso = crearCasoListoParaEgresoDePrueba('sgf-traspaso-faltante');
    $caso->registrosContablesCgu()->delete();
    $caso->update(['sgf_numero_traspaso' => null]);

    $casoFresco = CasoPagoProveedor::find($caso->id);

    expect(app(ListoParaEgresoResolver::class)->resuelve($casoFresco))->toBeFalse();
});

test('el detalle de un caso expone sgf_numero_traspaso en la respuesta', function () {
    $caso = crearCasoListoParaEgresoDePrueba('sgf-traspaso-serial');
    $caso->update(['sgf_numero_traspaso' => 'TR-2026-0087']);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.casos.show', $caso));

    $response->assertOk();
    $response->assertInertia(function (Assert $page) {
        $props = $page->toArray()['props']['caso'];

        expect($props['sgf_numero_traspaso'])->toBe('TR-2026-0087');
    });
});
