<?php

use App\Exceptions\ContratoException;
use App\Exceptions\TransicionWorkflowException;
use App\Models\Documento;
use App\Models\Proveedor;
use App\Models\TipoDocumento;
use App\Models\User;
use App\Services\Contratos\ContratoService;
use App\Services\Workflow\TransicionWorkflowService;
use Database\Seeders\WorkflowContratosSeeder;

beforeEach(function () {
    $this->seed(WorkflowContratosSeeder::class);
});

test('un contrato borrador transiciona a pendiente y aprobado con el documento CONTRATO vinculado', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76123456-7', 'nombre' => 'Proveedor SpA']);
    $contrato = app(ContratoService::class)->crear(datosContratoDePrueba(['proveedor_id' => $proveedor->id]));
    $workflow = app(TransicionWorkflowService::class);

    $workflow->execute($contrato->proceso, 'pendiente');
    expect($contrato->proceso->refresh()->estadoActual->codigo)->toBe('pendiente');

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('contratos.aprobar');

    expect(fn () => $workflow->execute($contrato->proceso, 'aprobar', user: $usuario))
        ->toThrow(TransicionWorkflowException::class);

    $tipoContrato = TipoDocumento::firstOrCreate(['codigo' => 'CONTRATO'], ['nombre' => 'Contrato']);
    $documento = Documento::create(['tipo_documento_id' => $tipoContrato->id]);
    $contrato->proceso->vinculosDocumento()->create(['documento_id' => $documento->id]);
    $documento->validaciones()->create(['estado' => 'valido', 'validado_en' => now()]);

    $resultado = $workflow->execute($contrato->proceso, 'aprobar', user: $usuario);

    expect($resultado->estadoActual->codigo)->toBe('aprobado');
});

test('un contrato en pendiente puede rechazarse con comentario', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76123456-7', 'nombre' => 'Proveedor SpA']);
    $contrato = app(ContratoService::class)->crear(datosContratoDePrueba(['proveedor_id' => $proveedor->id]));
    $workflow = app(TransicionWorkflowService::class);

    $workflow->execute($contrato->proceso, 'pendiente');

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('contratos.rechazar');

    $resultado = $workflow->execute($contrato->proceso, 'rechazar', comentario: 'Faltan antecedentes', user: $usuario);

    expect($resultado->estadoActual->codigo)->toBe('rechazado');
});

test('un contrato no puede editarse fuera de borrador', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76123456-7', 'nombre' => 'Proveedor SpA']);
    $contrato = app(ContratoService::class)->crear(datosContratoDePrueba(['proveedor_id' => $proveedor->id]));

    app(TransicionWorkflowService::class)->execute($contrato->proceso, 'pendiente');

    expect(fn () => app(ContratoService::class)->actualizar($contrato, ['referencia' => 'Nueva referencia']))
        ->toThrow(ContratoException::class);
});
