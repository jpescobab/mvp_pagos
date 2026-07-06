<?php

use App\Models\OrdenCompraMercadoPublico;
use App\Models\Proveedor;
use App\Models\User;
use Database\Seeders\IntegracionesSeeder;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return array<string, mixed>
 */
function ordenCrudaOcApiHttp(string $codigo, array $overrides = []): array
{
    return array_merge([
        'Codigo' => $codigo,
        'Estado' => 'Aceptada',
        'TipoMoneda' => 'CLP',
        'FormaPago' => '2',
        'TotalNeto' => 100000,
        'Total' => 119000,
        'Fechas' => [
            'FechaEnvio' => '2026-04-20 09:15:00',
            'FechaAceptacion' => '2026-05-01 14:30:00',
        ],
        'Comprador' => [
            'NombreOrganismo' => 'Corporación Administrativa del Poder Judicial',
            'NombreUnidad' => 'Corte de Apelaciones',
            'RutUnidad' => '60.503.000-9',
        ],
        'Proveedor' => [
            'RutSucursal' => '76.123.456-7',
            'Nombre' => 'Proveedor de Prueba SpA',
        ],
        'Items' => [
            'Listado' => [
                ['CodigoProducto' => 'A-1', 'Producto' => 'Resma de papel', 'Cantidad' => 10, 'PrecioNeto' => 5000, 'Total' => 50000],
            ],
        ],
    ], $overrides);
}

/**
 * Envuelve la orden en la forma real de la respuesta de Mercado Público:
 * `{"Cantidad": 1, "Listado": [{...}]}`.
 *
 * @return array<string, mixed>
 */
function payloadCrudoOcApiHttp(string $codigo, array $overrides = []): array
{
    return [
        'Cantidad' => 1,
        'Version' => 'v1',
        'Listado' => [ordenCrudaOcApiHttp($codigo, $overrides)],
    ];
}

function usuarioConPermisoOrdenCompraMpHttp(): User
{
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('adquisiciones.consultar_orden_compra_mp');

    return $usuario;
}

beforeEach(function () {
    $this->withoutVite();
    $this->seed(IntegracionesSeeder::class);
});

test('buscar una OC que existe localmente no consulta la API y muestra el registro local', function () {
    $orden = OrdenCompraMercadoPublico::factory()->create(['codigo' => 'OC-HTTP-LOCAL']);
    $usuario = usuarioConPermisoOrdenCompraMpHttp();

    Http::fake(['*/ordenesdecompra.json*' => Http::response(payloadCrudoOcApiHttp('OC-HTTP-LOCAL'), 200)]);

    $response = $this->actingAs($usuario)->get(route('adquisiciones.ordenes_compra_mp.index', ['codigo' => 'OC-HTTP-LOCAL']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('adquisiciones/ordenes-compra-mercado-publico/buscar', shouldExist: false)
        ->where('ordenLocal.codigo', 'OC-HTTP-LOCAL')
    );
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'ordenesdecompra'));
});

test('buscar un código inexistente localmente consulta la API y muestra la vista previa cuando la encuentra', function () {
    Http::fake(['*/ordenesdecompra.json*' => Http::response(payloadCrudoOcApiHttp('OC-HTTP-NUEVA'), 200)]);
    $usuario = usuarioConPermisoOrdenCompraMpHttp();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.ordenes_compra_mp.index', ['codigo' => 'OC-HTTP-NUEVA']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('adquisiciones/ordenes-compra-mercado-publico/buscar', shouldExist: false)
        ->where('vistaPrevia.payload_normalizado.codigo', 'OC-HTTP-NUEVA')
        ->where('vistaPrevia.proveedor_existente', null)
        ->where('vistaPrevia.payload_crudo.Listado.0.Codigo', 'OC-HTTP-NUEVA')
    );
});

test('el detalle de una OC guardada expone el payload crudo del snapshot que la originó para "Ver JSON"', function () {
    Http::fake(['*/ordenesdecompra.json*' => Http::response(payloadCrudoOcApiHttp('OC-HTTP-JSON'), 200)]);
    $usuario = usuarioConPermisoOrdenCompraMpHttp();

    $this->actingAs($usuario)->post(route('adquisiciones.ordenes_compra_mp.guardar'), ['codigo' => 'OC-HTTP-JSON']);

    $orden = OrdenCompraMercadoPublico::where('codigo', 'OC-HTTP-JSON')->firstOrFail();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.ordenes_compra_mp.show', $orden));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('orden.payload_crudo.Listado.0.Codigo', 'OC-HTTP-JSON')
    );
});

test('buscar un código inexistente en ningún lado informa que la OC no fue encontrada', function () {
    Http::fake(['*/ordenesdecompra.json*' => Http::response([], 200)]);
    $usuario = usuarioConPermisoOrdenCompraMpHttp();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.ordenes_compra_mp.index', ['codigo' => 'OC-HTTP-INEXISTENTE']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('adquisiciones/ordenes-compra-mercado-publico/buscar', shouldExist: false)
        ->where('noEncontrada', true)
    );
});

test('buscar sin el permiso requerido es rechazado', function () {
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.ordenes_compra_mp.index', ['codigo' => 'OC-HTTP-SIN-PERMISO']));

    $response->assertForbidden();
});

test('verificar una OC local contra la API muestra las diferencias sin modificar el registro', function () {
    $orden = OrdenCompraMercadoPublico::factory()->create([
        'codigo' => 'OC-HTTP-VERIFICAR',
        'estado_mercado_publico' => 'Enviada',
    ]);
    Http::fake(['*/ordenesdecompra.json*' => Http::response(payloadCrudoOcApiHttp('OC-HTTP-VERIFICAR'), 200)]);
    $usuario = usuarioConPermisoOrdenCompraMpHttp();

    $response = $this->actingAs($usuario)->post(route('adquisiciones.ordenes_compra_mp.verificar', $orden));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('comparacion.encontrada', true)
        ->where('comparacion.diferencias.estado_mercado_publico.api', 'Aceptada')
    );
    expect($orden->refresh()->estado_mercado_publico)->toBe('Enviada');
});

test('confirmar la actualización aplica los datos más recientes de la API al registro local', function () {
    $orden = OrdenCompraMercadoPublico::factory()->create([
        'codigo' => 'OC-HTTP-ACTUALIZAR',
        'estado_mercado_publico' => 'Enviada',
    ]);
    Http::fake(['*/ordenesdecompra.json*' => Http::response(payloadCrudoOcApiHttp('OC-HTTP-ACTUALIZAR'), 200)]);
    $usuario = usuarioConPermisoOrdenCompraMpHttp();

    $response = $this->actingAs($usuario)->post(route('adquisiciones.ordenes_compra_mp.actualizar', $orden));

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('adquisiciones.ordenes_compra_mp.show', $orden));
    expect($orden->refresh()->estado_mercado_publico)->toBe('Aceptada');
});

test('guardar una OC nueva con proveedor existente crea el registro con sus ítems y redirige al índice', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76.123.456-7', 'nombre' => 'Proveedor de Prueba SpA', 'activo' => true]);
    Http::fake(['*/ordenesdecompra.json*' => Http::response(payloadCrudoOcApiHttp('OC-HTTP-GUARDAR'), 200)]);
    $usuario = usuarioConPermisoOrdenCompraMpHttp();

    $response = $this->actingAs($usuario)->post(route('adquisiciones.ordenes_compra_mp.guardar'), [
        'codigo' => 'OC-HTTP-GUARDAR',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('adquisiciones.ordenes_compra_mp.index'));
    $orden = OrdenCompraMercadoPublico::where('codigo', 'OC-HTTP-GUARDAR')->first();
    expect($orden)->not->toBeNull();
    expect($orden->proveedor_id)->toBe($proveedor->id);
    expect($orden->items()->count())->toBe(1);
});

test('guardar una OC nueva sin proveedor existente lo crea automáticamente en la misma operación', function () {
    Http::fake(['*/ordenesdecompra.json*' => Http::response(payloadCrudoOcApiHttp('OC-HTTP-SIN-PROVEEDOR'), 200)]);
    $usuario = usuarioConPermisoOrdenCompraMpHttp();

    $response = $this->actingAs($usuario)->post(route('adquisiciones.ordenes_compra_mp.guardar'), [
        'codigo' => 'OC-HTTP-SIN-PROVEEDOR',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('adquisiciones.ordenes_compra_mp.index'));
    $orden = OrdenCompraMercadoPublico::where('codigo', 'OC-HTTP-SIN-PROVEEDOR')->first();
    expect($orden)->not->toBeNull();
    expect($orden->proveedor)->not->toBeNull();
    expect($orden->proveedor->rutproveedor)->toBe('76123456-7');
});
