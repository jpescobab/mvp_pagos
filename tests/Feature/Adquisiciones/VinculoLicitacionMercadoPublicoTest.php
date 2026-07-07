<?php

use App\Models\Institucion;
use App\Models\LicitacionMercadoPublico;
use App\Models\ModalidadAdquisicion;
use App\Models\ProcesoAdquisicion;
use App\Models\User;
use App\Services\Adquisiciones\ProcesoAdquisicionService;
use Database\Seeders\IntegracionesSeeder;
use Database\Seeders\ModalidadesAdquisicionSeeder;
use Database\Seeders\WorkflowAdquisicionesSeeder;

function crearProcesoAdquisicionParaVinculoLic(): ProcesoAdquisicion
{
    $institucion = Institucion::create(['codigo' => 'CAPJ-VINC-LIC', 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => '14-VINC-LIC', 'nombre' => 'Zonal']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => 'CF-VINC-LIC', 'nombre' => 'Centro Financiero']);
    $ccosto = $cfinanciero->ccostos()->create(['codigo' => 'CC-VINC-LIC', 'nombre' => 'Centro de Costo']);

    return app(ProcesoAdquisicionService::class)->crear([
        'codigo' => 'ADQ-VINC-LIC-'.fake()->unique()->numerify('#####'),
        'modalidad_id' => ModalidadAdquisicion::where('codigo', 'LICITACION_PUBLICA')->value('id'),
        'ccosto_id' => $ccosto->id,
        'objeto' => 'Adquisición de prueba para vínculo de Licitación',
    ]);
}

function usuarioConPermisoLicitacionMp(): User
{
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('adquisiciones.consultar_licitacion_mp');

    return $usuario;
}

beforeEach(function () {
    $this->seed(IntegracionesSeeder::class);
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
});

test('vincular una Licitación a un proceso de adquisición guarda la referencia y no altera su workflow', function () {
    $licitacion = LicitacionMercadoPublico::factory()->create();
    $proceso = crearProcesoAdquisicionParaVinculoLic();
    $estadoAntes = $proceso->proceso->estado_actual_id;
    $usuario = usuarioConPermisoLicitacionMp();

    $response = $this->actingAs($usuario)->post(
        route('adquisiciones.licitaciones_mp.vinculo.store', $licitacion),
        ['proceso_adquisicion_id' => $proceso->id],
    );

    $response->assertSessionHasNoErrors();
    expect($licitacion->refresh()->proceso_adquisicion_id)->toBe($proceso->id);
    expect($proceso->proceso->refresh()->estado_actual_id)->toBe($estadoAntes);
});

test('desvincular una Licitación de su proceso de adquisición quita la referencia', function () {
    $proceso = crearProcesoAdquisicionParaVinculoLic();
    $licitacion = LicitacionMercadoPublico::factory()->create(['proceso_adquisicion_id' => $proceso->id]);
    $usuario = usuarioConPermisoLicitacionMp();

    $response = $this->actingAs($usuario)->delete(route('adquisiciones.licitaciones_mp.vinculo.destroy', $licitacion));

    $response->assertSessionHasNoErrors();
    expect($licitacion->refresh()->proceso_adquisicion_id)->toBeNull();
});

test('vincular una Licitación sin el permiso requerido es rechazado', function () {
    $licitacion = LicitacionMercadoPublico::factory()->create();
    $proceso = crearProcesoAdquisicionParaVinculoLic();
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(
        route('adquisiciones.licitaciones_mp.vinculo.store', $licitacion),
        ['proceso_adquisicion_id' => $proceso->id],
    );

    $response->assertForbidden();
    expect($licitacion->refresh()->proceso_adquisicion_id)->toBeNull();
});
