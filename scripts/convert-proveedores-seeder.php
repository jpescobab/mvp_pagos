<?php

/**
 * One-off conversion script: parses the MySQL-flavored raw SQL seeder from the
 * erp project (INSERT IGNORE, not valid on PostgreSQL) and regenerates it as a
 * Laravel insertOrIgnore() seeder for this project. Not part of the app runtime.
 */
$sourcePath = 'C:\\laragon\\www\\erp\\database\\seeders\\ProveedoresSeeder.php';
$destPath = __DIR__.'/../database/seeders/ProveedoresSeeder.php';

$source = file_get_contents($sourcePath);
$lines = explode("\n", $source);

$rows = [];
foreach ($lines as $line) {
    if (! str_contains($line, 'INSERT IGNORE INTO proveedores')) {
        continue;
    }

    preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $line, $matches);
    $fields = $matches[1] ?? [];

    if (count($fields) !== 6) {
        fwrite(STDERR, "Skipping unparsable line (found {$fields} fields): {$line}\n");

        continue;
    }

    [$rutproveedor, $nombre, $correo, $direccion, $contacto, $imagen] = $fields;

    $rows[] = [
        'rutproveedor' => $rutproveedor,
        'nombre' => $nombre,
        'correo' => $correo,
        'direccion' => $direccion,
        'contacto' => $contacto,
        'imagen' => $imagen,
    ];
}

fwrite(STDERR, 'Parsed '.count($rows)." proveedor rows.\n");

function phpExport(string $value): string
{
    return $value === '' ? 'null' : "'".str_replace("'", "\\'", $value)."'";
}

$chunks = array_chunk($rows, 100);

$out = "<?php\n\nnamespace Database\\Seeders;\n\nuse Illuminate\\Database\\Seeder;\nuse Illuminate\\Support\\Facades\\DB;\n\nclass ProveedoresSeeder extends Seeder\n{\n    /**\n     * Seed the real proveedores of CAPJ Zonal Coyhaique.\n     */\n    public function run(): void\n    {\n";

foreach ($chunks as $chunk) {
    $out .= "        DB::table('proveedores')->insertOrIgnore([\n";
    foreach ($chunk as $row) {
        $out .= sprintf(
            "            ['rutproveedor' => %s, 'nombre' => %s, 'correo' => %s, 'direccion' => %s, 'contacto' => %s, 'imagen' => %s],\n",
            phpExport($row['rutproveedor']),
            phpExport($row['nombre']),
            phpExport($row['correo']),
            phpExport($row['direccion']),
            phpExport($row['contacto']),
            phpExport($row['imagen']),
        );
    }
    $out .= "        ]);\n\n";
}

$out .= "    }\n}\n";

file_put_contents($destPath, $out);

fwrite(STDERR, "Wrote {$destPath}\n");
