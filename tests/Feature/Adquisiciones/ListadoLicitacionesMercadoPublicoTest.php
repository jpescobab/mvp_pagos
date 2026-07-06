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
use Inertia\Testing\AssertableInertia as Assert;

function crearProcesoAdquisicionParaListadoLic(): ProcesoAdquisicion
{
    $institucion = Institucion::create(['codigo' => 'CAPJ-LIST-LIC', 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => '14-LIST-LIC', 'nombre' => 'Zonal']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => 'CF-LIST-LIC', 'nombre' => 'Centro Financiero']);
    $ccosto = $cfinanciero->ccostos()->create(['codigo' => 'CC-LIST-LIC', 'nombre' => 'Centro de Costo']);

    return app(ProcesoAdquisicionService::class)->crear([
        'codigo' => 'ADQ-2026-LIC-001',
        'modalidad_id' => ModalidadAdquisicion::where('codigo', 'LICITACION_PUBLICA')->value('id'),
        'ccosto_id' => $ccosto->id,
        'objeto' => 'Adquisición de prueba para listado de Licitación',
    ]);
}

function usuarioConPermisoLicitacionMpListado(): User
{
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('adquisiciones.consultar_licitacion_mp');

    return $usuario;
}

beforeEach(function () {
    $this->withoutVite();
    $this->seed(IntegracionesSeeder::class);
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
});

test('el listado está vacío cuando no hay Licitaciones guardadas', function () {
    $usuario = usuarioConPermisoLicitacionMpListado();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.licitaciones_mp.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('adquisiciones/licitaciones-mercado-publico/index')
        ->where('licitaciones.data', [])
    );
});

test('el listado muestra las Licitaciones guardadas con su proceso de adquisición', function () {
    $proceso = crearProcesoAdquisicionParaListadoLic();
    LicitacionMercadoPublico::factory()->create([
        'codigo' => 'LIC-LISTADO-1',
        'proceso_adquisicion_id' => $proceso->id,
    ]);
    $usuario = usuarioConPermisoLicitacionMpListado();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.licitaciones_mp.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('adquisiciones/licitaciones-mercado-publico/index')
        ->where('licitaciones.data.0.codigo', 'LIC-LISTADO-1')
        ->where('licitaciones.data.0.proceso_adquisicion.codigo', 'ADQ-2026-LIC-001')
    );
});

test('una Licitación sin proceso de adquisición vinculado se lista sin error', function () {
    LicitacionMercadoPublico::factory()->create(['codigo' => 'LIC-LISTADO-SIN-VINCULO']);
    $usuario = usuarioConPermisoLicitacionMpListado();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.licitaciones_mp.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('licitaciones.data.0.codigo', 'LIC-LISTADO-SIN-VINCULO')
        ->missing('licitaciones.data.0.proceso_adquisicion')
    );
});

test('filtrar el listado por código no navega a la página de búsqueda', function () {
    LicitacionMercadoPublico::factory()->create(['codigo' => 'LIC-LISTADO-FILTRO']);
    LicitacionMercadoPublico::factory()->create(['codigo' => 'LIC-OTRA']);
    $usuario = usuarioConPermisoLicitacionMpListado();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.licitaciones_mp.index', ['q' => 'FILTRO']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('adquisiciones/licitaciones-mercado-publico/index')
        ->where('licitaciones.data.0.codigo', 'LIC-LISTADO-FILTRO')
        ->where('q', 'FILTRO')
        ->count('licitaciones.data', 1)
    );
});

test('el listado pagina 20 registros por página', function () {
    LicitacionMercadoPublico::factory()->count(25)->create();
    $usuario = usuarioConPermisoLicitacionMpListado();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.licitaciones_mp.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->count('licitaciones.data', 20)
        ->where('licitaciones.meta.total', 25)
    );
});

test('acceder al listado sin el permiso requerido es rechazado', function () {
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.licitaciones_mp.index'));

    $response->assertForbidden();
});

test('el flag nuevo renderiza el formulario de búsqueda por código en vez del listado', function () {
    $usuario = usuarioConPermisoLicitacionMpListado();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.licitaciones_mp.index', ['nuevo' => 1]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('adquisiciones/licitaciones-mercado-publico/buscar')
        ->where('codigo', null)
    );
});
