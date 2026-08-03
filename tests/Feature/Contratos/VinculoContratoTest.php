<?php

use App\Models\AuditLog;
use App\Models\Funcionario;
use App\Models\Institucion;
use App\Models\OrdenCompraMercadoPublico;
use App\Models\ProcesoAdquisicion;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\Adquisiciones\ProcesoAdquisicionService;
use App\Services\Contratos\ContratoService;
use Database\Seeders\IntegracionesSeeder;
use Database\Seeders\ModalidadesAdquisicionSeeder;
use Database\Seeders\WorkflowAdquisicionesSeeder;
use Database\Seeders\WorkflowContratosSeeder;

function crearProcesoAdquisicionParaVinculoContrato(): ProcesoAdquisicion
{
    $institucion = Institucion::create(['codigo' => 'CAPJ-CTR', 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => '14-CTR', 'nombre' => 'Zonal']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => 'CF-CTR', 'nombre' => 'Centro Financiero']);
    $ccosto = $cfinanciero->ccostos()->create(['codigo' => 'CC-CTR', 'nombre' => 'Centro de Costo']);
    $funcionario = Funcionario::create([
        'rut' => fake()->unique()->numerify('#########'),
        'nombre' => fake()->name(),
        'ccosto_id' => $ccosto->id,
        'activo' => true,
    ]);

    return app(ProcesoAdquisicionService::class)->crear([
        'fecha_inicio' => now()->toDateString(),
        'nombre' => 'Adquisición de prueba para vínculo de contrato',
        'ccosto_id' => $ccosto->id,
        'funcionario_requirente_id' => $funcionario->id,
        'caracteristicas' => 'Adquisición de prueba',
        'motivo_contratacion' => 'Motivo de prueba',
        'en_plan_compras' => false,
        'convenio_marco' => true,
        'monto_estimado_solicitado' => 100000,
    ]);
}

beforeEach(function () {
    $this->seed(WorkflowContratosSeeder::class);
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
    $this->seed(IntegracionesSeeder::class);
});

test('vincular un contrato a un proceso de adquisición no altera el workflow de ninguno de los dos', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76123456-7', 'nombre' => 'Proveedor SpA']);
    $contrato = app(ContratoService::class)->crear(datosContratoDePrueba(['proveedor_id' => $proveedor->id]));
    $procesoAdquisicion = crearProcesoAdquisicionParaVinculoContrato();

    $estadoContratoAntes = $contrato->proceso->estado_actual_id;
    $estadoProcesoAntes = $procesoAdquisicion->proceso->estado_actual_id;

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('contratos.editar');

    $response = $this->actingAs($usuario)->post(
        route('contratos.vinculo_proceso_adquisicion.store', $contrato),
        ['proceso_adquisicion_id' => $procesoAdquisicion->id],
    );

    $response->assertSessionHasNoErrors();
    expect($contrato->refresh()->proceso_adquisicion_id)->toBe($procesoAdquisicion->id);
    expect($contrato->proceso->refresh()->estado_actual_id)->toBe($estadoContratoAntes);
    expect($procesoAdquisicion->proceso->refresh()->estado_actual_id)->toBe($estadoProcesoAntes);
});

test('desvincular un contrato de su proceso de adquisición quita la referencia', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76123456-7', 'nombre' => 'Proveedor SpA']);
    $procesoAdquisicion = crearProcesoAdquisicionParaVinculoContrato();
    $contrato = app(ContratoService::class)->crear(datosContratoDePrueba([
        'proveedor_id' => $proveedor->id,
        'proceso_adquisicion_id' => $procesoAdquisicion->id,
    ]));

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('contratos.editar');

    $response = $this->actingAs($usuario)->delete(route('contratos.vinculo_proceso_adquisicion.destroy', $contrato));

    $response->assertSessionHasNoErrors();
    expect($contrato->refresh()->proceso_adquisicion_id)->toBeNull();
});

test('vincular una OC a un Contrato guarda la referencia y audita la acción', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76123456-7', 'nombre' => 'Proveedor SpA']);
    $contrato = app(ContratoService::class)->crear(datosContratoDePrueba(['proveedor_id' => $proveedor->id]));
    $orden = OrdenCompraMercadoPublico::factory()->create();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('adquisiciones.consultar_orden_compra_mp');

    $response = $this->actingAs($usuario)->post(
        route('adquisiciones.ordenes_compra_mp.vinculo_contrato.store', $orden),
        ['contrato_id' => $contrato->id],
    );

    $response->assertSessionHasNoErrors();
    expect($orden->refresh()->contrato_id)->toBe($contrato->id);
    expect(AuditLog::where('action', 'orden_compra_mercado_publico.vincular_contrato')->exists())->toBeTrue();
});

test('una OC sin vínculo a Contrato se muestra sin error', function () {
    $orden = OrdenCompraMercadoPublico::factory()->create();

    expect($orden->contrato_id)->toBeNull();
    expect($orden->contrato)->toBeNull();
});
