<?php

use App\Exceptions\ContratoException;
use App\Models\Proveedor;
use App\Services\Contratos\ContratoService;
use Database\Seeders\WorkflowContratosSeeder;

beforeEach(function () {
    $this->seed(WorkflowContratosSeeder::class);
});

test('agregar un ítem de convenio de precio a un contrato habilitado en borrador', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76123456-7', 'nombre' => 'Proveedor SpA']);
    $contrato = app(ContratoService::class)->crear(
        datosContratoDePrueba(['proveedor_id' => $proveedor->id, 'tiene_convenio_precio' => true]),
    );

    $item = app(ContratoService::class)->agregarItemConvenioPrecio($contrato, [
        'descripcion' => 'Bidón de agua purificada 20L',
        'unidad_medida' => 'unidad',
        'precio_unitario' => 5000,
        'moneda' => 'CLP',
    ]);

    expect($item->contrato_id)->toBe($contrato->id);
    expect($contrato->itemsConvenioPrecio()->count())->toBe(1);
});

test('agregar un ítem de convenio a un contrato sin convenio de precios es rechazado', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76123456-7', 'nombre' => 'Proveedor SpA']);
    $contrato = app(ContratoService::class)->crear(
        datosContratoDePrueba(['proveedor_id' => $proveedor->id, 'tiene_convenio_precio' => false]),
    );

    expect(fn () => app(ContratoService::class)->agregarItemConvenioPrecio($contrato, [
        'descripcion' => 'Ítem no permitido',
        'precio_unitario' => 1000,
    ]))->toThrow(ContratoException::class);
});
