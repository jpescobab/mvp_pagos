<?php

use App\Models\ClienteMedidor;
use Database\Seeders\CcostosSeeder;
use Database\Seeders\CfinancierosSeeder;
use Database\Seeders\ClientesMedidoresSeeder;
use Database\Seeders\CoreInstitucionalSeeder;
use Database\Seeders\ProveedoresSeeder;

beforeEach(function () {
    $this->seed(CoreInstitucionalSeeder::class);
    $this->seed(CfinancierosSeeder::class);
    $this->seed(CcostosSeeder::class);
    $this->seed(ProveedoresSeeder::class);
    $this->seed(ClientesMedidoresSeeder::class);
});

test('siembra los 39 clientes medidores reales', function () {
    expect(ClienteMedidor::count())->toBe(39);
});

test('un cliente medidor resuelve su ccosto_id por código, no por jurisdicción', function () {
    $cliente = ClienteMedidor::where('numero_cliente', '10122596')->firstOrFail();

    expect($cliente->ccosto->codigo)->toBe('1400010201');
    expect($cliente->ccosto->nombre)->toBe('CAPJ ZONAL COYHAIQUE');
});

test('un cliente medidor resuelve su proveedor de servicio eléctrico', function () {
    $cliente = ClienteMedidor::where('numero_cliente', '10122596')->firstOrFail();

    expect($cliente->proveedor->nombre)->toBe('EMPRESA ELECTRICA DE AYSEN S.A.');
});

test('un cliente sin override usa el ccosto por defecto', function () {
    $cliente = ClienteMedidor::where('numero_cliente', '10091997')->firstOrFail();

    expect($cliente->ccosto->codigo)->toBe('1400010201');
});
