<?php

use App\Exceptions\ContratoSinProveedorException;
use App\Models\Contrato;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\Contratos\ContratoService;
use Database\Seeders\WorkflowContratosSeeder;
use Illuminate\Support\Facades\Schema;

/**
 * @return array<string, mixed>
 */
function datosContratoDePrueba(array $overrides = []): array
{
    return array_merge([
        'id_institucional' => fake()->unique()->numberBetween(10000, 99999),
        'modalidad_compra' => 'licitacion',
        'id_proceso_mp' => '2182-1-LE26',
        'tipo_contrato' => 'contrato',
        'referencia' => 'Contrato de prueba',
        'fecha_inicio_vigencia' => '2026-01-01',
        'fecha_fin_vigencia' => '2026-12-31',
        'materia' => 'SERVICIOS GENERALES',
        'submateria' => 'MANTENCIÓN DE JARDINES',
        'tiene_convenio_precio' => false,
        'tiene_calendario_pago' => false,
    ], $overrides);
}

beforeEach(function () {
    $this->seed(WorkflowContratosSeeder::class);
});

test('crear un contrato con proveedor_id existente queda en borrador', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76123456-7', 'nombre' => 'Proveedor de Prueba SpA']);

    $contrato = app(ContratoService::class)->crear(
        datosContratoDePrueba(['proveedor_id' => $proveedor->id]),
    );

    expect($contrato->codigo)->toStartWith('CTR-');
    expect($contrato->proveedor_id)->toBe($proveedor->id);
    expect($contrato->proceso->estadoActual->codigo)->toBe('borrador');
});

test('crear un contrato resuelve el proveedor por RUT cuando no existe', function () {
    $contrato = app(ContratoService::class)->crear(
        datosContratoDePrueba(),
        ['rutproveedor' => '76.123.456-7', 'nombre' => 'Proveedor Nuevo SpA'],
    );

    $proveedor = Proveedor::where('rutproveedor', '76123456-7')->first();

    expect($proveedor)->not->toBeNull();
    expect($contrato->proveedor_id)->toBe($proveedor->id);
});

test('crear un contrato completa campos vacíos de un proveedor existente sin sobrescribir los cargados', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76.123.456-7', 'nombre' => 'Nombre Ya Cargado SpA']);

    app(ContratoService::class)->crear(
        datosContratoDePrueba(),
        ['rutproveedor' => '76.123.456-7', 'nombre' => 'Otro Nombre', 'correo' => 'contacto@proveedor.cl'],
    );

    $proveedor->refresh();

    expect($proveedor->nombre)->toBe('Nombre Ya Cargado SpA');
    expect($proveedor->correo)->toBe('contacto@proveedor.cl');
});

test('crear un contrato sin RUT identificable y sin proveedor_id es rechazado', function () {
    expect(fn () => app(ContratoService::class)->crear(datosContratoDePrueba(), ['nombre' => 'Sin RUT']))
        ->toThrow(ContratoSinProveedorException::class);

    expect(Contrato::count())->toBe(0);
});

test('el id_institucional persiste, se indexa como único y no admite duplicados vía el endpoint de creación', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76123456-7', 'nombre' => 'Proveedor de Prueba SpA']);

    $contrato = app(ContratoService::class)->crear(
        datosContratoDePrueba(['proveedor_id' => $proveedor->id, 'id_institucional' => 26417]),
    );

    expect($contrato->id_institucional)->toBe(26417);
    expect(Schema::hasColumn('contratos', 'id_institucional'))->toBeTrue();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('contratos.crear');

    $response = $this->actingAs($usuario)->post(route('contratos.store'), [
        ...datosContratoDePrueba(['proveedor_id' => $proveedor->id, 'id_institucional' => 26417]),
    ]);

    $response->assertSessionHasErrors('id_institucional');
    expect(Contrato::where('id_institucional', 26417)->count())->toBe(1);
});
