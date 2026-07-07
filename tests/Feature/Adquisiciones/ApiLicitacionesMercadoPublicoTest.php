<?php

use App\Models\LicitacionMercadoPublico;
use App\Models\User;
use Database\Seeders\IntegracionesSeeder;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return array<string, mixed>
 */
function licitacionCrudaLicApiHttp(string $codigo, array $overrides = []): array
{
    return array_merge([
        'CodigoExterno' => $codigo,
        'Nombre' => 'NEUMÁTICOS PARA EQUIPOS VARIOS',
        'Estado' => 'Publicada',
        'CodigoEstado' => 5,
        'Moneda' => 'CLP',
        'MontoEstimado' => null,
        'Comprador' => [
            'NombreOrganismo' => 'Corporación Administrativa del Poder Judicial',
            'NombreUnidad' => 'Corte de Apelaciones',
            'RutUnidad' => '60.503.000-9',
        ],
        'Fechas' => [
            'FechaCreacion' => '2026-07-02T09:10:56.203',
            'FechaCierre' => '2026-07-17T15:30:00',
            'FechaPublicacion' => '2026-07-06T11:12:19.3',
        ],
        'Adjudicacion' => null,
        'Items' => [
            'Listado' => [
                ['Correlativo' => 1, 'CodigoProducto' => 25172503, 'Categoria' => 'Neumáticos', 'NombreProducto' => 'Neumático', 'Descripcion' => 'Neumático 275/70R22.5', 'UnidadMedida' => 'Unidad', 'Cantidad' => 7.0, 'Adjudicacion' => null],
            ],
        ],
    ], $overrides);
}

/**
 * Envuelve la licitación en la forma real de la respuesta de Mercado Público:
 * `{"Cantidad": 1, "Listado": [{...}]}`.
 *
 * @return array<string, mixed>
 */
function payloadCrudoLicApiHttp(string $codigo, array $overrides = []): array
{
    return [
        'Cantidad' => 1,
        'Version' => 'v1',
        'Listado' => [licitacionCrudaLicApiHttp($codigo, $overrides)],
    ];
}

function usuarioConPermisoLicitacionMpHttp(): User
{
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('adquisiciones.consultar_licitacion_mp');

    return $usuario;
}

beforeEach(function () {
    $this->withoutVite();
    $this->seed(IntegracionesSeeder::class);
});

test('buscar una Licitación que existe localmente no consulta la API y muestra el registro local', function () {
    LicitacionMercadoPublico::factory()->create(['codigo' => 'LIC-HTTP-LOCAL']);
    $usuario = usuarioConPermisoLicitacionMpHttp();

    Http::fake(['*/licitaciones.json*' => Http::response(payloadCrudoLicApiHttp('LIC-HTTP-LOCAL'), 200)]);

    $response = $this->actingAs($usuario)->get(route('adquisiciones.licitaciones_mp.index', ['codigo' => 'LIC-HTTP-LOCAL']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('adquisiciones/licitaciones-mercado-publico/buscar', shouldExist: false)
        ->where('licitacionLocal.codigo', 'LIC-HTTP-LOCAL')
    );
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'licitaciones.json'));
});

test('buscar un código inexistente localmente consulta la API y muestra la vista previa cuando la encuentra', function () {
    Http::fake(['*/licitaciones.json*' => Http::response(payloadCrudoLicApiHttp('LIC-HTTP-NUEVA'), 200)]);
    $usuario = usuarioConPermisoLicitacionMpHttp();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.licitaciones_mp.index', ['codigo' => 'LIC-HTTP-NUEVA']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('adquisiciones/licitaciones-mercado-publico/buscar', shouldExist: false)
        ->where('vistaPrevia.payload_normalizado.codigo', 'LIC-HTTP-NUEVA')
        ->where('vistaPrevia.payload_crudo.Listado.0.CodigoExterno', 'LIC-HTTP-NUEVA')
    );
});

test('el detalle de una Licitación guardada expone el payload crudo del snapshot que la originó para "Ver JSON"', function () {
    Http::fake(['*/licitaciones.json*' => Http::response(payloadCrudoLicApiHttp('LIC-HTTP-JSON'), 200)]);
    $usuario = usuarioConPermisoLicitacionMpHttp();

    $this->actingAs($usuario)->post(route('adquisiciones.licitaciones_mp.guardar'), ['codigo' => 'LIC-HTTP-JSON']);

    $licitacion = LicitacionMercadoPublico::where('codigo', 'LIC-HTTP-JSON')->firstOrFail();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.licitaciones_mp.show', $licitacion));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('licitacion.payload_crudo.Listado.0.CodigoExterno', 'LIC-HTTP-JSON')
    );
});

test('buscar un código inexistente en ningún lado informa que la Licitación no fue encontrada', function () {
    Http::fake(['*/licitaciones.json*' => Http::response([], 200)]);
    $usuario = usuarioConPermisoLicitacionMpHttp();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.licitaciones_mp.index', ['codigo' => 'LIC-HTTP-INEXISTENTE']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('adquisiciones/licitaciones-mercado-publico/buscar', shouldExist: false)
        ->where('noEncontrada', true)
    );
});

test('buscar sin el permiso requerido es rechazado', function () {
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('adquisiciones.licitaciones_mp.index', ['codigo' => 'LIC-HTTP-SIN-PERMISO']));

    $response->assertForbidden();
});

test('verificar una Licitación local contra la API muestra las diferencias sin modificar el registro', function () {
    $licitacion = LicitacionMercadoPublico::factory()->create([
        'codigo' => 'LIC-HTTP-VERIFICAR',
        'estado_mercado_publico' => 'Publicada',
    ]);
    Http::fake(['*/licitaciones.json*' => Http::response(payloadCrudoLicApiHttp('LIC-HTTP-VERIFICAR', ['Estado' => 'Adjudicada']), 200)]);
    $usuario = usuarioConPermisoLicitacionMpHttp();

    $response = $this->actingAs($usuario)->post(route('adquisiciones.licitaciones_mp.verificar', $licitacion));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('comparacion.encontrada', true)
        ->where('comparacion.diferencias.estado_mercado_publico.api', 'Adjudicada')
    );
    expect($licitacion->refresh()->estado_mercado_publico)->toBe('Publicada');
});

test('confirmar la actualización aplica los datos más recientes de la API al registro local', function () {
    $licitacion = LicitacionMercadoPublico::factory()->create([
        'codigo' => 'LIC-HTTP-ACTUALIZAR',
        'estado_mercado_publico' => 'Publicada',
    ]);
    Http::fake(['*/licitaciones.json*' => Http::response(payloadCrudoLicApiHttp('LIC-HTTP-ACTUALIZAR', ['Estado' => 'Adjudicada']), 200)]);
    $usuario = usuarioConPermisoLicitacionMpHttp();

    $response = $this->actingAs($usuario)->post(route('adquisiciones.licitaciones_mp.actualizar', $licitacion));

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('adquisiciones.licitaciones_mp.show', $licitacion));
    expect($licitacion->refresh()->estado_mercado_publico)->toBe('Adjudicada');
});

test('guardar una Licitación nueva crea el registro con sus ítems y redirige al índice', function () {
    Http::fake(['*/licitaciones.json*' => Http::response(payloadCrudoLicApiHttp('LIC-HTTP-GUARDAR'), 200)]);
    $usuario = usuarioConPermisoLicitacionMpHttp();

    $response = $this->actingAs($usuario)->post(route('adquisiciones.licitaciones_mp.guardar'), [
        'codigo' => 'LIC-HTTP-GUARDAR',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('adquisiciones.licitaciones_mp.index'));
    $licitacion = LicitacionMercadoPublico::where('codigo', 'LIC-HTTP-GUARDAR')->first();
    expect($licitacion)->not->toBeNull();
    expect($licitacion->items()->count())->toBe(1);
});

test('guardar sin el permiso requerido es rechazado', function () {
    Http::fake(['*/licitaciones.json*' => Http::response(payloadCrudoLicApiHttp('LIC-HTTP-SIN-PERMISO'), 200)]);
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(route('adquisiciones.licitaciones_mp.guardar'), [
        'codigo' => 'LIC-HTTP-SIN-PERMISO',
    ]);

    $response->assertForbidden();
});
