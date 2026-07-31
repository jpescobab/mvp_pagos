<?php

use App\Models\Funcionario;
use App\Models\Institucion;
use App\Models\OrdenCompraMercadoPublico;
use App\Models\ProcesoAdquisicion;
use App\Models\User;
use App\Services\Adquisiciones\ProcesoAdquisicionService;
use Database\Seeders\IntegracionesSeeder;
use Database\Seeders\ModalidadesAdquisicionSeeder;
use Database\Seeders\WorkflowAdquisicionesSeeder;

function crearProcesoAdquisicionParaVinculoOc(): ProcesoAdquisicion
{
    $institucion = Institucion::create(['codigo' => 'CAPJ-VINC', 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => '14-VINC', 'nombre' => 'Zonal']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => 'CF-VINC', 'nombre' => 'Centro Financiero']);
    $ccosto = $cfinanciero->ccostos()->create(['codigo' => 'CC-VINC', 'nombre' => 'Centro de Costo']);
    $funcionario = Funcionario::create([
        'rut' => fake()->unique()->numerify('#########'),
        'nombre' => fake()->name(),
        'ccosto_id' => $ccosto->id,
        'activo' => true,
    ]);

    return app(ProcesoAdquisicionService::class)->crear([
        'fecha_inicio' => now()->toDateString(),
        'nombre' => 'Adquisición de prueba para vínculo de OC',
        'ccosto_id' => $ccosto->id,
        'funcionario_requirente_id' => $funcionario->id,
        'caracteristicas' => 'Adquisición de prueba para vínculo de OC',
        'motivo_contratacion' => 'Motivo de prueba',
        'en_plan_compras' => false,
        'convenio_marco' => true,
        'monto_estimado_solicitado' => 100000,
    ]);
}

function usuarioConPermisoOrdenCompraMp(): User
{
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('adquisiciones.consultar_orden_compra_mp');

    return $usuario;
}

beforeEach(function () {
    $this->seed(IntegracionesSeeder::class);
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
});

test('vincular una OC a un proceso de adquisición guarda la referencia y no altera su workflow', function () {
    $orden = OrdenCompraMercadoPublico::factory()->create();
    $proceso = crearProcesoAdquisicionParaVinculoOc();
    $estadoAntes = $proceso->proceso->estado_actual_id;
    $usuario = usuarioConPermisoOrdenCompraMp();

    $response = $this->actingAs($usuario)->post(
        route('adquisiciones.ordenes_compra_mp.vinculo.store', $orden),
        ['proceso_adquisicion_id' => $proceso->id],
    );

    $response->assertSessionHasNoErrors();
    expect($orden->refresh()->proceso_adquisicion_id)->toBe($proceso->id);
    expect($proceso->proceso->refresh()->estado_actual_id)->toBe($estadoAntes);
});

test('desvincular una OC de su proceso de adquisición quita la referencia', function () {
    $proceso = crearProcesoAdquisicionParaVinculoOc();
    $orden = OrdenCompraMercadoPublico::factory()->create(['proceso_adquisicion_id' => $proceso->id]);
    $usuario = usuarioConPermisoOrdenCompraMp();

    $response = $this->actingAs($usuario)->delete(route('adquisiciones.ordenes_compra_mp.vinculo.destroy', $orden));

    $response->assertSessionHasNoErrors();
    expect($orden->refresh()->proceso_adquisicion_id)->toBeNull();
});

test('vincular una OC sin el permiso requerido es rechazado', function () {
    $orden = OrdenCompraMercadoPublico::factory()->create();
    $proceso = crearProcesoAdquisicionParaVinculoOc();
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(
        route('adquisiciones.ordenes_compra_mp.vinculo.store', $orden),
        ['proceso_adquisicion_id' => $proceso->id],
    );

    $response->assertForbidden();
    expect($orden->refresh()->proceso_adquisicion_id)->toBeNull();
});
