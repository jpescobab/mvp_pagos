<?php

use App\Models\IndicadorEconomico;
use App\Models\IndicadorEconomicoImportacion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function crearIndicadorDePrueba(array $atributos): IndicadorEconomico
{
    $importacion = IndicadorEconomicoImportacion::create(['tipo_importacion' => 'diaria_usd', 'estado' => 'success']);

    return IndicadorEconomico::create([
        'importacion_id' => $importacion->id,
        'nombre' => 'Indicador de prueba',
        'tipo' => 'moneda',
        'periodicidad_valor' => 'diaria',
        'unidad_medida' => 'CLP',
        'moneda_base' => 'CLP',
        'fuente' => 'CMF',
        ...$atributos,
    ]);
}

test('listar indicadores sin filtro devuelve todos los códigos', function () {
    $this->withoutVite();
    crearIndicadorDePrueba(['codigo' => 'UF', 'fecha_valor' => '2026-06-10', 'valor' => 40765.97]);
    crearIndicadorDePrueba(['codigo' => 'USD', 'fecha_valor' => '2026-06-10', 'valor' => 916.97]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('indicadores-economicos.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('indicadores-economicos/index')
        ->has('indicadores.data', 2)
    );
});

test('filtrar por código devuelve solo los indicadores de ese código', function () {
    $this->withoutVite();
    crearIndicadorDePrueba(['codigo' => 'UF', 'fecha_valor' => '2026-06-10', 'valor' => 40765.97]);
    crearIndicadorDePrueba(['codigo' => 'USD', 'fecha_valor' => '2026-06-10', 'valor' => 916.97]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('indicadores-economicos.index', ['codigo' => 'UF']));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('indicadores-economicos/index')
        ->has('indicadores.data', 1)
        ->where('indicadores.data.0.codigo', 'UF')
        ->where('codigo', 'UF')
    );
});

test('un usuario no autenticado es redirigido al login', function () {
    $response = $this->get(route('indicadores-economicos.index'));

    $response->assertRedirect(route('login'));
});
