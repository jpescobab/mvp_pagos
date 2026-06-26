<?php

use App\Models\Asignacion;
use App\Models\Catalogo;
use App\Models\Item;
use Database\Seeders\AsignacionesSeeder;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\ItemsSeeder;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->seed(ItemsSeeder::class);
    $this->seed(AsignacionesSeeder::class);
    $this->seed(CatalogosSeeder::class);
});

test('siembra los 12 items, 57 asignaciones y 156 catalogos reales', function () {
    expect(Item::count())->toBe(12);
    expect(Asignacion::count())->toBe(57);
    expect(Catalogo::count())->toBe(156);
});

test('una asignación resuelve su item_id por código', function () {
    $asignacion = Asignacion::where('codigo', '2204001000')->firstOrFail();

    expect($asignacion->item->codigo)->toBe('2204');
});

test('un catálogo resuelve su item_id por código directamente, sin pasar por asignación', function () {
    $catalogo = Catalogo::where('codigo', '2208999001')->firstOrFail();

    expect($catalogo->item->codigo)->toBe('2208');
});

test('el código de item es único', function () {
    Item::create(['codigo' => '2201', 'nombre' => 'Duplicado']);
})->throws(QueryException::class);
