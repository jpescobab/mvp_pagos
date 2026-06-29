<?php

use App\Models\Ccosto;
use App\Models\Cfinanciero;
use App\Models\ClienteMedidor;
use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('un usuario autenticado puede listar el catálogo de clientes medidores', function () {
    $institucion = Institucion::create(['codigo' => 'INST-1', 'nombre' => 'Institución de Prueba']);
    $jurisdiccion = Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => 'JUR-1', 'nombre' => 'Jurisdicción de Prueba']);
    $cfinanciero = Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => 'CF-1', 'nombre' => 'Cfinanciero de Prueba']);
    $ccosto = Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => 'CC-1', 'nombre' => 'Ccosto de Prueba']);

    ClienteMedidor::create([
        'numero_cliente' => 'CLI-001',
        'ccosto_id' => $ccosto->id,
        'tipo_suministro' => 'electrico',
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('maestros.clientes-medidores.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/clientes-medidores/index')
        ->has('clientes', 1)
        ->where('clientes.0.numero_cliente', 'CLI-001')
        ->where('clientes.0.ccosto.codigo', 'CC-1')
    );
});

test('un usuario no autenticado es redirigido al login', function () {
    $response = $this->get(route('maestros.clientes-medidores.index'));

    $response->assertRedirect(route('login'));
});
