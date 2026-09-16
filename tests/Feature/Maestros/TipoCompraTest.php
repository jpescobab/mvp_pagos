<?php

use App\Models\Ccosto;
use App\Models\DetalleFactura;
use App\Models\Factura;
use App\Models\Institucion;
use App\Models\Proveedor;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\TipoCompra;
use App\Models\User;
use App\Services\PagoProveedores\CasoPagoProveedorImporter;
use Database\Seeders\WorkflowPagoProveedoresSeeder;

function crearCcostoDePruebaParaTipoCompra(): Ccosto
{
    $sufijo = fake()->unique()->numerify('####');

    $institucion = Institucion::create(['codigo' => "CAPJ-TC-{$sufijo}", 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => "14-TC-{$sufijo}", 'nombre' => 'Zonal']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => "CF-TC-{$sufijo}", 'nombre' => 'Centro Financiero']);

    return $cfinanciero->ccostos()->create(['codigo' => "CC-TC-{$sufijo}", 'nombre' => 'Centro de Costo']);
}

beforeEach(function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
});

test('un usuario con permiso puede crear, editar y eliminar un tipo de compra', function () {
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.administrar_tipos_compra');

    $response = $this->actingAs($usuario)->post(route('maestros.tipos-compra.store'), [
        'codigo' => 'SERVICIOS_TECNICOS',
        'nombre' => 'Servicios técnicos',
        'activo' => true,
    ]);
    $response->assertSessionHasNoErrors();

    $tipoCompra = TipoCompra::where('codigo', 'SERVICIOS_TECNICOS')->first();
    expect($tipoCompra)->not->toBeNull();

    $response = $this->actingAs($usuario)->patch(route('maestros.tipos-compra.update', $tipoCompra), [
        'codigo' => 'SERVICIOS_TECNICOS',
        'nombre' => 'Servicios Técnicos Especializados',
        'activo' => false,
    ]);
    $response->assertSessionHasNoErrors();
    expect($tipoCompra->refresh()->nombre)->toBe('Servicios Técnicos Especializados');
    expect($tipoCompra->activo)->toBeFalse();

    $response = $this->actingAs($usuario)->delete(route('maestros.tipos-compra.destroy', $tipoCompra));
    $response->assertSessionHasNoErrors();
    expect(TipoCompra::find($tipoCompra->id))->toBeNull();
});

test('no se puede eliminar un tipo de compra con detalles de factura asociados', function () {
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.administrar_tipos_compra');

    $tipoCompra = TipoCompra::create(['codigo' => 'OBRAS_TEST', 'nombre' => 'Obras', 'activo' => true]);
    $ccosto = crearCcostoDePruebaParaTipoCompra();

    $proveedor = Proveedor::create(['rutproveedor' => fake()->unique()->numerify('########-#'), 'nombre' => 'Proveedor de Prueba SpA']);
    $sistema = SistemaExterno::firstOrCreate(
        ['codigo' => 'SGF'],
        ['nombre' => 'SGF', 'tipo_integracion' => 'playwright', 'activo' => true],
    );
    $normalizado = [
        'sgf_id' => 'sgf-tipo-compra-1',
        'estado' => 'EN_TRAMITE',
        'grupo_actual' => 'FINANZAS',
        'observaciones' => null,
        'rut' => $proveedor->rutproveedor,
        'monto' => 500000.0,
    ];
    $snapshot = SnapshotDatosExterno::create([
        'sistema_externo_id' => $sistema->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => $normalizado['sgf_id'],
        'payload_crudo' => $normalizado,
        'payload_normalizado' => $normalizado,
        'hash' => hash('sha256', json_encode($normalizado, JSON_THROW_ON_ERROR)),
        'capturado_en' => now(),
    ]);
    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot($snapshot);
    $factura = Factura::create([
        'caso_pago_proveedor_id' => $caso->id,
        'proveedor_id' => $caso->proveedor_id,
        'folio' => 'F-tipo-compra-1',
        'monto' => 500000,
        'fecha_emision' => '2026-06-01',
    ]);
    DetalleFactura::create([
        'factura_id' => $factura->id,
        'tipo_compra_id' => $tipoCompra->id,
        'ccosto_id' => $ccosto->id,
        'cantidad' => 1,
        'monto' => 500000,
    ]);

    $response = $this->actingAs($usuario)->delete(route('maestros.tipos-compra.destroy', $tipoCompra));

    $response->assertSessionHasNoErrors();
    expect(TipoCompra::find($tipoCompra->id))->not->toBeNull();
});

test('un usuario sin permiso no puede administrar tipos de compra', function () {
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(route('maestros.tipos-compra.store'), [
        'codigo' => 'NUEVO',
        'nombre' => 'Nuevo',
    ]);

    $response->assertForbidden();
    expect(TipoCompra::where('codigo', 'NUEVO')->exists())->toBeFalse();
});
