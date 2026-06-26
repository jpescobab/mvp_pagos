<?php

/**
 * One-off conversion script: extracts the literal PHP arrays from the erp
 * ClientesMedidoresSeeder (via eval, letting PHP's own parser handle it) and
 * regenerates an equivalent seeder for this project, mapped to ccosto_id
 * instead of the source project's ad-hoc "Jurisdiccion" model. Not part of
 * the app runtime.
 */
$source = file_get_contents('C:\\laragon\\www\\erp\\database\\seeders\\ClientesMedidoresSeeder.php');

preg_match('/\$ccostoPorCliente = (\[.*?\]);/s', $source, $m1);
$ccostoPorCliente = eval('return '.$m1[1].';');

preg_match('/\$clientes = (\[.*?\]\s*);/s', $source, $m2);
$clientes = eval('return '.$m2[1].';');

preg_match("/\\\$defaultCcosto\\s*=\\s*'([^']+)'/", $source, $m3);
$defaultCcosto = $m3[1];

fwrite(STDERR, 'clientes: '.count($clientes).', ccostoPorCliente overrides: '.count($ccostoPorCliente)."\n");

function esc(?string $value): string
{
    if ($value === null || trim((string) $value) === '') {
        return 'null';
    }

    return "'".str_replace("'", "\\'", $value)."'";
}

$rows = [];
foreach ($clientes as $c) {
    $ccosto = $ccostoPorCliente[$c['numerocliente']] ?? $defaultCcosto;
    $rows[] = [
        'numero_cliente' => $c['numerocliente'],
        'rutproveedor' => $c['rutproveedor'],
        'ccosto' => $ccosto,
        'tipo_suministro' => strtolower($c['tipo_suministro']),
        'direccion' => $c['direccion'],
        'activo' => $c['activo'] ? 'true' : 'false',
    ];
}

$out = "<?php\n\nnamespace Database\\Seeders;\n\nuse App\\Models\\Ccosto;\nuse App\\Models\\ClienteMedidor;\nuse App\\Models\\Proveedor;\nuse Illuminate\\Database\\Seeder;\n\nclass ClientesMedidoresSeeder extends Seeder\n{\n    /**\n     * Seed the real clientes medidores (electricity meters) of Zonal\n     * Coyhaique, resolved to ccosto_id by codigo (the source project calls\n     * this \"Jurisdiccion\", but it maps 1:1 to our ccostos by the same codes).\n     */\n    public function run(): void\n    {\n        \$clientes = [\n";
foreach ($rows as $row) {
    $out .= sprintf(
        "            ['numero_cliente' => %s, 'rutproveedor' => %s, 'ccosto' => %s, 'tipo_suministro' => %s, 'direccion' => %s, 'activo' => %s],\n",
        esc($row['numero_cliente']),
        esc($row['rutproveedor']),
        esc($row['ccosto']),
        esc($row['tipo_suministro']),
        esc($row['direccion']),
        $row['activo'],
    );
}
$out .= "        ];\n\n        foreach (\$clientes as \$cliente) {\n            \$ccosto = Ccosto::where('codigo', \$cliente['ccosto'])->firstOrFail();\n            \$proveedor = Proveedor::where('rutproveedor', \$cliente['rutproveedor'])->first();\n\n            ClienteMedidor::firstOrCreate(\n                ['numero_cliente' => \$cliente['numero_cliente']],\n                [\n                    'ccosto_id' => \$ccosto->id,\n                    'proveedor_id' => \$proveedor?->id,\n                    'tipo_suministro' => \$cliente['tipo_suministro'],\n                    'direccion_suministro' => \$cliente['direccion'],\n                    'activo' => \$cliente['activo'],\n                ],\n            );\n        }\n    }\n}\n";

file_put_contents(__DIR__.'/../database/seeders/ClientesMedidoresSeeder.php', $out);

fwrite(STDERR, "Done.\n");
