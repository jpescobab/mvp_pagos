<?php

namespace Database\Seeders;

use App\Models\Ccosto;
use App\Models\ClienteMedidor;
use App\Models\Proveedor;
use Illuminate\Database\Seeder;

class ClientesMedidoresSeeder extends Seeder
{
    /**
     * Seed the real clientes medidores (electricity meters) of Zonal
     * Coyhaique, resolved to ccosto_id by codigo (the source project calls
     * this "Jurisdiccion", but it maps 1:1 to our ccostos by the same codes).
     */
    public function run(): void
    {
        $clientes = [
            ['numero_cliente' => '10122596', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => 'BLANCA', 'activo' => true],
            ['numero_cliente' => '10123101', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => 'BT4-3', 'activo' => true],
            ['numero_cliente' => '10127837', 'rutproveedor' => '88272600-2', 'ccosto' => '1400020301', 'tipo_suministro' => 'electricidad', 'direccion' => 'AT4-3', 'activo' => true],
            ['numero_cliente' => '10127838', 'rutproveedor' => '88272600-2', 'ccosto' => '1400020301', 'tipo_suministro' => 'electricidad', 'direccion' => 'AT4-1', 'activo' => true],
            ['numero_cliente' => '10138053', 'rutproveedor' => '88272600-2', 'ccosto' => '1400020601', 'tipo_suministro' => 'electricidad', 'direccion' => 'BT4-3', 'activo' => true],
            ['numero_cliente' => '10115714', 'rutproveedor' => '88272600-2', 'ccosto' => '1400020601', 'tipo_suministro' => 'electricidad', 'direccion' => 'BLANCA', 'activo' => true],
            ['numero_cliente' => '10117895', 'rutproveedor' => '88272600-2', 'ccosto' => '1400020602', 'tipo_suministro' => 'electricidad', 'direccion' => 'BT3-B', 'activo' => true],
            ['numero_cliente' => '10105641', 'rutproveedor' => '88272600-2', 'ccosto' => '1400020603', 'tipo_suministro' => 'electricidad', 'direccion' => 'BT3-B', 'activo' => true],
            ['numero_cliente' => '10117896', 'rutproveedor' => '88272600-2', 'ccosto' => '1400020603', 'tipo_suministro' => 'electricidad', 'direccion' => 'BLANCA', 'activo' => true],
            ['numero_cliente' => '10101389', 'rutproveedor' => '88272600-2', 'ccosto' => '1400020401', 'tipo_suministro' => 'electricidad', 'direccion' => 'BT1', 'activo' => true],
            ['numero_cliente' => '10101388', 'rutproveedor' => '88272600-2', 'ccosto' => '1400020401', 'tipo_suministro' => 'electricidad', 'direccion' => 'BT1', 'activo' => true],
            ['numero_cliente' => '10129956', 'rutproveedor' => '88272600-2', 'ccosto' => '1400020401', 'tipo_suministro' => 'electricidad', 'direccion' => 'BT4-3', 'activo' => true],
            ['numero_cliente' => '10126102', 'rutproveedor' => '88272600-2', 'ccosto' => '1400020401', 'tipo_suministro' => 'electricidad', 'direccion' => 'BLANCA', 'activo' => true],
            ['numero_cliente' => '10094414', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => 'BT1', 'activo' => true],
            ['numero_cliente' => '10098536', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => 'BT3-PP', 'activo' => true],
            ['numero_cliente' => '10115703', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => 'BLANCA', 'activo' => true],
            ['numero_cliente' => '10135754', 'rutproveedor' => '88272600-2', 'ccosto' => '1471031301', 'tipo_suministro' => 'electricidad', 'direccion' => 'BT-43', 'activo' => true],
            ['numero_cliente' => '10115704', 'rutproveedor' => '88272600-2', 'ccosto' => '1471031301', 'tipo_suministro' => 'electricidad', 'direccion' => 'BLANCA', 'activo' => true],
            ['numero_cliente' => '10118753', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => null, 'activo' => false],
            ['numero_cliente' => '10113130', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => null, 'activo' => false],
            ['numero_cliente' => '10103242', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => null, 'activo' => false],
            ['numero_cliente' => '10116551', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => 'Ramon Freire 293', 'activo' => false],
            ['numero_cliente' => '10122085', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => 'Ramon Freire 293', 'activo' => false],
            ['numero_cliente' => '10124561', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => 'Moraleda 448', 'activo' => false],
            ['numero_cliente' => '1011401', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => null, 'activo' => true],
            ['numero_cliente' => '10091997', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => null, 'activo' => true],
            ['numero_cliente' => '10099037', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => null, 'activo' => true],
            ['numero_cliente' => '10099168', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => null, 'activo' => true],
            ['numero_cliente' => '10100106', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => null, 'activo' => true],
            ['numero_cliente' => '10100511', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => null, 'activo' => true],
            ['numero_cliente' => '10117916', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => null, 'activo' => true],
            ['numero_cliente' => '10119499', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => null, 'activo' => true],
            ['numero_cliente' => '10119500', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => null, 'activo' => true],
            ['numero_cliente' => '10119501', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => null, 'activo' => true],
            ['numero_cliente' => '10119502', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => null, 'activo' => true],
            ['numero_cliente' => '10119503', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => null, 'activo' => true],
            ['numero_cliente' => '10119504', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => null, 'activo' => true],
            ['numero_cliente' => '10122862', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => null, 'activo' => true],
            ['numero_cliente' => '10122863', 'rutproveedor' => '88272600-2', 'ccosto' => '1400010201', 'tipo_suministro' => 'electricidad', 'direccion' => null, 'activo' => true],
        ];

        foreach ($clientes as $cliente) {
            $ccosto = Ccosto::where('codigo', $cliente['ccosto'])->firstOrFail();
            $proveedor = Proveedor::where('rutproveedor', $cliente['rutproveedor'])->first();

            ClienteMedidor::firstOrCreate(
                ['numero_cliente' => $cliente['numero_cliente']],
                [
                    'ccosto_id' => $ccosto->id,
                    'proveedor_id' => $proveedor?->id,
                    'tipo_suministro' => $cliente['tipo_suministro'],
                    'direccion_suministro' => $cliente['direccion'],
                    'activo' => $cliente['activo'],
                ],
            );
        }
    }
}
