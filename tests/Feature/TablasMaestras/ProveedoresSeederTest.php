<?php

use App\Models\Proveedor;
use Database\Seeders\ProveedoresSeeder;
use Illuminate\Database\QueryException;

test('el seeder crea los 977 proveedores reales con rutproveedor único', function () {
    $this->seed(ProveedoresSeeder::class);

    expect(Proveedor::count())->toBe(977);

    $proveedor = Proveedor::where('rutproveedor', '88272600-2')->firstOrFail();
    expect($proveedor->nombre)->toBe('EMPRESA ELECTRICA DE AYSEN S.A.');
});

test('el rutproveedor es único', function () {
    $this->seed(ProveedoresSeeder::class);

    Proveedor::create(['rutproveedor' => '88272600-2', 'nombre' => 'Duplicado']);
})->throws(QueryException::class);
