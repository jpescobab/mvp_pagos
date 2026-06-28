<?php

use App\Models\IndicadorEconomico;
use App\Models\IndicadorEconomicoImportacion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function crearIndicadorDePrueba(array $atributos): IndicadorEconomico
{
    $importacion = IndicadorEconomicoImportacion::create(['tipo' => 'diario', 'estado' => 'ok']);

    return IndicadorEconomico::create([
        'importacion_id' => $importacion->id,
        'periodicidad_valor' => 'diaria',
        'fuente' => 'CMF',
        ...$atributos,
    ]);
}

test('listar indicadores sin filtro devuelve todos los tipos', function () {
    $this->withoutVite();
    crearIndicadorDePrueba(['tipo' => 'UF', 'fecha_valor' => '2026-06-10', 'valor' => 40765.97]);
    crearIndicadorDePrueba(['tipo' => 'USD', 'fecha_valor' => '2026-06-10', 'valor' => 916.97]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('indicadores-economicos.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('indicadores-economicos/index')
        ->has('indicadores.data', 2)
    );
});

test('filtrar por tipo devuelve solo los indicadores de ese tipo', function () {
    $this->withoutVite();
    crearIndicadorDePrueba(['tipo' => 'UF', 'fecha_valor' => '2026-06-10', 'valor' => 40765.97]);
    crearIndicadorDePrueba(['tipo' => 'USD', 'fecha_valor' => '2026-06-10', 'valor' => 916.97]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('indicadores-economicos.index', ['tipo' => 'UF']));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('indicadores-economicos/index')
        ->has('indicadores.data', 1)
        ->where('indicadores.data.0.tipo', 'UF')
        ->where('tipo', 'UF')
    );
});

test('un usuario no autenticado es redirigido al login', function () {
    $response = $this->get(route('indicadores-economicos.index'));

    $response->assertRedirect(route('login'));
});
