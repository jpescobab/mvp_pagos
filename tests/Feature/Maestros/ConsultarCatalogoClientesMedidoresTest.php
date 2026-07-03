<?php

use App\Models\Ccosto;
use App\Models\Cfinanciero;
use App\Models\ClienteMedidor;
use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\Proveedor;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function crearCcostoParaClientesMedidores(): Ccosto
{
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);
    $jurisdiccion = Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Zonal Coyhaique']);
    $cfinanciero = Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Administracion Zonal']);

    return Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => '1400010201', 'nombre' => 'CAPJ Zonal Coyhaique']);
}

test('un usuario autenticado puede listar el catálogo de clientes medidores sin filtro', function () {
    $ccosto = crearCcostoParaClientesMedidores();
    ClienteMedidor::create(['numero_cliente' => '111-1', 'ccosto_id' => $ccosto->id, 'tipo_suministro' => 'Eléctrico']);
    ClienteMedidor::create(['numero_cliente' => '222-2', 'ccosto_id' => $ccosto->id, 'tipo_suministro' => 'Agua']);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('maestros.clientes-medidores.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/clientes-medidores/index')
        ->has('clientes.data', 2)
    );
});

test('buscar por número de cliente devuelve solo las coincidencias', function () {
    $ccosto = crearCcostoParaClientesMedidores();
    ClienteMedidor::create(['numero_cliente' => '111-1', 'ccosto_id' => $ccosto->id, 'tipo_suministro' => 'Eléctrico']);
    ClienteMedidor::create(['numero_cliente' => '222-2', 'ccosto_id' => $ccosto->id, 'tipo_suministro' => 'Agua']);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('maestros.clientes-medidores.index', ['q' => '222']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/clientes-medidores/index')
        ->has('clientes.data', 1)
        ->where('clientes.data.0.numero_cliente', '222-2')
    );
});

test('buscar por nombre de proveedor devuelve solo las coincidencias', function () {
    $ccosto = crearCcostoParaClientesMedidores();
    $proveedor = Proveedor::create(['rutproveedor' => '11111111-1', 'nombre' => 'Aguas Patagonia']);
    ClienteMedidor::create(['numero_cliente' => '111-1', 'ccosto_id' => $ccosto->id, 'proveedor_id' => $proveedor->id, 'tipo_suministro' => 'Agua']);
    ClienteMedidor::create(['numero_cliente' => '222-2', 'ccosto_id' => $ccosto->id, 'tipo_suministro' => 'Eléctrico']);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('maestros.clientes-medidores.index', ['q' => 'Patagonia']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/clientes-medidores/index')
        ->has('clientes.data', 1)
        ->where('clientes.data.0.numero_cliente', '111-1')
    );
});

test('buscar por código de centro de costo devuelve solo las coincidencias', function () {
    $ccosto = crearCcostoParaClientesMedidores();
    $otroCcosto = Ccosto::create(['cfinanciero_id' => $ccosto->cfinanciero_id, 'codigo' => '1400020301', 'nombre' => 'Corte de Apelaciones']);
    ClienteMedidor::create(['numero_cliente' => '111-1', 'ccosto_id' => $ccosto->id, 'tipo_suministro' => 'Agua']);
    ClienteMedidor::create(['numero_cliente' => '222-2', 'ccosto_id' => $otroCcosto->id, 'tipo_suministro' => 'Eléctrico']);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('maestros.clientes-medidores.index', ['q' => '020301']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/clientes-medidores/index')
        ->has('clientes.data', 1)
        ->where('clientes.data.0.numero_cliente', '222-2')
    );
});

test('un usuario no autenticado es redirigido al login', function () {
    $response = $this->get(route('maestros.clientes-medidores.index'));

    $response->assertRedirect(route('login'));
});
