<?php

use App\Models\Institucion;
use App\Models\LicitacionMercadoPublico;
use App\Models\ModalidadAdquisicion;
use App\Models\OrdenCompraMercadoPublico;
use App\Models\ProcesoAdquisicion;
use App\Models\User;
use App\Services\Adquisiciones\ProcesoAdquisicionService;
use Database\Seeders\IntegracionesSeeder;
use Database\Seeders\ModalidadesAdquisicionSeeder;
use Database\Seeders\WorkflowAdquisicionesSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function crearProcesoParaCompras(): ProcesoAdquisicion
{
    $institucion = Institucion::create(['codigo' => 'CAPJ-CMP', 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => '14-CMP', 'nombre' => 'Zonal']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => 'CF-CMP', 'nombre' => 'Centro Financiero']);
    $ccosto = $cfinanciero->ccostos()->create(['codigo' => 'CC-CMP', 'nombre' => 'Centro de Costo']);

    return app(ProcesoAdquisicionService::class)->crear([
        'codigo' => 'ADQ-CMP-'.fake()->unique()->numerify('#####'),
        'modalidad_id' => ModalidadAdquisicion::where('codigo', 'LICITACION_PUBLICA')->value('id'),
        'ccosto_id' => $ccosto->id,
        'objeto' => 'Proceso para mostrar compras de Mercado Público',
    ]);
}

function usuarioConsultaProceso(): User
{
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('adquisiciones.consultar_proceso');

    return $usuario;
}

beforeEach(function () {
    $this->seed(IntegracionesSeeder::class);
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
});

test('el detalle del proceso expone sus OC y licitaciones de Mercado Público vinculadas', function () {
    $proceso = crearProcesoParaCompras();
    $orden = OrdenCompraMercadoPublico::factory()->create(['proceso_adquisicion_id' => $proceso->id]);
    $licitacion = LicitacionMercadoPublico::factory()->create(['proceso_adquisicion_id' => $proceso->id]);

    $this->actingAs(usuarioConsultaProceso())
        ->get(route('adquisiciones.procesos.show', $proceso))
        ->assertInertia(fn (Assert $page) => $page
            ->component('adquisiciones/procesos/show')
            ->where('proceso.ordenes_compra_mercado_publico', fn ($ocs) => collect($ocs)->firstWhere('id', $orden->id)['codigo'] === $orden->codigo)
            ->where('proceso.licitaciones_mercado_publico', fn ($lics) => collect($lics)->firstWhere('id', $licitacion->id)['codigo'] === $licitacion->codigo));
});

test('un proceso sin compras vinculadas expone colecciones vacías', function () {
    $proceso = crearProcesoParaCompras();

    $this->actingAs(usuarioConsultaProceso())
        ->get(route('adquisiciones.procesos.show', $proceso))
        ->assertInertia(fn (Assert $page) => $page
            ->component('adquisiciones/procesos/show')
            ->where('proceso.ordenes_compra_mercado_publico', [])
            ->where('proceso.licitaciones_mercado_publico', []));
});

test('la relación licitacionesMercadoPublico devuelve solo las licitaciones del proceso', function () {
    $proceso = crearProcesoParaCompras();
    $licitacion = LicitacionMercadoPublico::factory()->create(['proceso_adquisicion_id' => $proceso->id]);
    LicitacionMercadoPublico::factory()->create();

    expect($proceso->licitacionesMercadoPublico()->pluck('id')->all())->toBe([$licitacion->id]);
});
