<?php

use App\Exceptions\CertificadoDisponibilidadPresupuestariaException;
use App\Models\IndicadorEconomico;
use App\Models\IndicadorEconomicoImportacion;
use App\Models\User;
use App\Services\Presupuesto\CrearBorradorCertificadoDisponibilidadService;
use Database\Seeders\WorkflowPresupuestoCdpSeeder;

function crearIndicadorEconomicoParaCdpDePrueba(array $atributos): IndicadorEconomico
{
    $importacion = IndicadorEconomicoImportacion::create(['tipo_importacion' => 'diaria_uf', 'estado' => 'success']);

    return IndicadorEconomico::create([
        'importacion_id' => $importacion->id,
        'nombre' => 'Indicador de prueba',
        'tipo' => 'moneda',
        'periodicidad_valor' => 'diaria',
        'unidad_medida' => 'CLP',
        'moneda_base' => 'CLP',
        'fuente' => 'CMF',
        ...$atributos,
    ]);
}

test('crear un cdp en UF resuelve paridad y monto desde el indicador económico real', function () {
    crearIndicadorEconomicoParaCdpDePrueba(['codigo' => 'UF', 'fecha_valor' => '2026-07-30', 'valor' => 39731.72]);

    $presupuesto = crearLineaPresupuestoDePrueba();
    $cdp = crearCdpBorradorDePrueba($presupuesto, [
        'moneda_compra' => 'UF',
        'total_moneda_compra' => 0.50,
        'fecha_paridad' => '2026-07-30',
        'monto' => null,
    ]);

    expect((float) $cdp->paridad)->toBe(39731.72);
    expect((float) $cdp->monto)->toBe(19865.86);
});

test('crear un cdp en una moneda sin indicador para esa fecha lanza una excepción', function () {
    (new WorkflowPresupuestoCdpSeeder)->run();
    $presupuesto = crearLineaPresupuestoDePrueba();

    expect(fn () => app(CrearBorradorCertificadoDisponibilidadService::class)->crear(
        datosCdpDePrueba($presupuesto, [
            'moneda_compra' => 'USD',
            'total_moneda_compra' => 100,
            'fecha_paridad' => '2020-01-01',
        ]),
    ))->toThrow(CertificadoDisponibilidadPresupuestariaException::class);
});

test('un cdp en CLP no tiene paridad y el monto es igual al total', function () {
    $cdp = crearCdpBorradorDePrueba(null, ['total_moneda_compra' => 250000]);

    expect($cdp->paridad)->toBeNull();
    expect($cdp->fecha_paridad)->toBeNull();
    expect((float) $cdp->monto)->toBe(250000.0);
});

test('nombre_iniciativa es requerido cuando tipo_gasto es INI', function () {
    (new WorkflowPresupuestoCdpSeeder)->run();
    $presupuesto = crearLineaPresupuestoDePrueba();
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('presupuesto.crear_cdp');

    $response = $this->actingAs($usuario)->post(route('presupuesto.cdps.store'), datosCdpDePrueba($presupuesto, [
        'tipo_gasto' => 'INI',
        'codigo_iniciativa' => 'INV-OC-26CY-0000',
    ]));

    $response->assertInvalid(['nombre_iniciativa']);
});

test('el endpoint de previsualización de paridad devuelve el valor del indicador', function () {
    crearIndicadorEconomicoParaCdpDePrueba(['codigo' => 'UF', 'fecha_valor' => '2026-07-30', 'valor' => 39731.72]);
    (new WorkflowPresupuestoCdpSeeder)->run();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('presupuesto.crear_cdp');

    $response = $this->actingAs($usuario)->getJson(route('presupuesto.cdps.paridad', ['moneda' => 'UF', 'fecha' => '2026-07-30']));

    $response->assertOk();
    $response->assertJson(['valor' => 39731.72]);
});

test('el endpoint de previsualización de paridad responde 404 si no hay valor para la fecha', function () {
    (new WorkflowPresupuestoCdpSeeder)->run();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('presupuesto.crear_cdp');

    $response = $this->actingAs($usuario)->getJson(route('presupuesto.cdps.paridad', ['moneda' => 'USD', 'fecha' => '2020-01-01']));

    $response->assertNotFound();
});
