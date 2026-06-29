<?php

use App\Models\SistemaExterno;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('un usuario autenticado puede listar el catálogo de sistemas externos', function () {
    SistemaExterno::create(['codigo' => 'SGF', 'nombre' => 'SGF', 'tipo_integracion' => 'manual', 'activo' => true]);
    SistemaExterno::create(['codigo' => 'CGU', 'nombre' => 'CGU', 'tipo_integracion' => 'manual', 'activo' => false]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('integraciones.sistemas-externos.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('integraciones/sistemas-externos/index')
        ->has('sistemas', 2)
        ->where('sistemas.0.codigo', 'CGU')
        ->where('sistemas.1.codigo', 'SGF')
    );
});

test('un usuario no autenticado es redirigido al login', function () {
    $response = $this->get(route('integraciones.sistemas-externos.index'));

    $response->assertRedirect(route('login'));
});
