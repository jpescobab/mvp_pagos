<?php

use App\Models\Institucion;
use App\Models\ModalidadAdquisicion;
use App\Models\OrdenCompraMercadoPublico;
use App\Models\ProcesoAdquisicion;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\Adquisiciones\ProcesoAdquisicionService;
use Database\Seeders\IntegracionesSeeder;
use Database\Seeders\ModalidadesAdquisicionSeeder;
use Database\Seeders\WorkflowAdquisicionesSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function crearProcesoAdquisicionParaListadoOc(): ProcesoAdquisicion
{
    $institucion = Institucion::create(['codigo' => 'CAPJ-LIST', 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => '14-LIST', 'nombre' => 'Zonal']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => 'CF-LIST', 'nombre' => 'Centro Financiero']);
    $ccosto = $cfinanciero->ccostos()->create(['codigo' => 'CC-LIST', 'nombre' => 'Centro de Costo']);

    return app(ProcesoAdquisicionService::class)->crear([
        'codigo' => 'ADQ-2026-001',
        'modalidad_id' => ModalidadAdquisicion::where('codigo', 'LICITACION_PUBLICA')->value('id'),
        'ccosto_id' => $ccosto->id,
        'objeto' => 'Adquisición de prueba para listado de OC',
    ]);
}

function usuarioConPermisoOrdenCompraMpListado(): User
{
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('adquisiciones.consultar_orden_compra_mp');

    return $usuario;
}

beforeEach(function () {
    $this->withoutVite();
    $this->seed(IntegracionesSeeder::class);
    $this->seed(ModalidadesAdquisicionSeeder::class);
    $this->seed(WorkflowAdquisicionesSeeder::class);
});

test('el listado está vacío cuando no hay OC guardadas', function () {
    $usuario = usuarioConPermisoOrdenCompraMpListado();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.ordenes_compra_mp.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('adquisiciones/ordenes-compra-mercado-publico/index')
        ->where('ordenes.data', [])
    );
});

test('el listado muestra las OC guardadas con su proveedor y proceso de adquisición', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76.123.456-7', 'nombre' => 'Proveedor de Prueba SpA', 'activo' => true]);
    $proceso = crearProcesoAdquisicionParaListadoOc();
    $orden = OrdenCompraMercadoPublico::factory()->create([
        'codigo' => 'OC-LISTADO-1',
        'proveedor_id' => $proveedor->id,
        'proceso_adquisicion_id' => $proceso->id,
    ]);
    $usuario = usuarioConPermisoOrdenCompraMpListado();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.ordenes_compra_mp.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('adquisiciones/ordenes-compra-mercado-publico/index')
        ->where('ordenes.data.0.codigo', 'OC-LISTADO-1')
        ->where('ordenes.data.0.proveedor.id', $proveedor->id)
        ->where('ordenes.data.0.proceso_adquisicion.codigo', 'ADQ-2026-001')
    );
    expect($orden->proveedor_id)->toBe($proveedor->id);
});

test('una OC sin proceso de adquisición vinculado se lista sin error', function () {
    OrdenCompraMercadoPublico::factory()->create(['codigo' => 'OC-LISTADO-SIN-VINCULO']);
    $usuario = usuarioConPermisoOrdenCompraMpListado();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.ordenes_compra_mp.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('ordenes.data.0.codigo', 'OC-LISTADO-SIN-VINCULO')
        ->missing('ordenes.data.0.proceso_adquisicion')
    );
});

test('filtrar el listado por código no navega a la página de búsqueda', function () {
    OrdenCompraMercadoPublico::factory()->create(['codigo' => 'OC-LISTADO-FILTRO']);
    OrdenCompraMercadoPublico::factory()->create(['codigo' => 'OC-OTRA']);
    $usuario = usuarioConPermisoOrdenCompraMpListado();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.ordenes_compra_mp.index', ['q' => 'FILTRO']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('adquisiciones/ordenes-compra-mercado-publico/index')
        ->where('ordenes.data.0.codigo', 'OC-LISTADO-FILTRO')
        ->where('q', 'FILTRO')
        ->count('ordenes.data', 1)
    );
});

test('el listado pagina 20 registros por página', function () {
    OrdenCompraMercadoPublico::factory()->count(25)->create();
    $usuario = usuarioConPermisoOrdenCompraMpListado();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.ordenes_compra_mp.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->count('ordenes.data', 20)
        ->where('ordenes.meta.total', 25)
    );
});

test('acceder al listado sin el permiso requerido es rechazado', function () {
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.ordenes_compra_mp.index'));

    $response->assertForbidden();
});

test('el flag nuevo renderiza el formulario de búsqueda por código en vez del listado', function () {
    $usuario = usuarioConPermisoOrdenCompraMpListado();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.ordenes_compra_mp.index', ['nuevo' => 1]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('adquisiciones/ordenes-compra-mercado-publico/buscar')
        ->where('codigo', null)
    );
});
