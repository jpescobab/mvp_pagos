<?php

/**
 * One-off conversion script: extracts the literal PHP array passed to
 * insertOrIgnore() in each erp seeder (letting PHP's own parser handle the
 * irregular source formatting via eval, rather than a fragile text regex)
 * and regenerates equivalent seeders for this project. Not part of the app
 * runtime.
 */
function extractInsertOrIgnoreArray(string $path): array
{
    $source = file_get_contents($path);

    $start = strpos($source, 'insertOrIgnore([');
    $arrayStart = $start + strlen('insertOrIgnore(');
    $end = strrpos($source, ']);');
    $arrayLiteral = substr($source, $arrayStart, $end - $arrayStart + 1);

    return eval('return '.$arrayLiteral.';');
}

function esc(?string $value): string
{
    if ($value === null || trim((string) $value) === '') {
        return 'null';
    }

    return "'".str_replace("'", "\\'", $value)."'";
}

// --- items ---
$items = extractInsertOrIgnoreArray('C:\\laragon\\www\\erp\\database\\seeders\\ItemsSeeder.php');
fwrite(STDERR, 'items: '.count($items)."\n");

$out = "<?php\n\nnamespace Database\\Seeders;\n\nuse App\\Models\\Item;\nuse Illuminate\\Database\\Seeder;\n\nclass ItemsSeeder extends Seeder\n{\n    /**\n     * Seed the real budget classifier items (subtitulo 22).\n     */\n    public function run(): void\n    {\n        \$items = [\n";
foreach ($items as $row) {
    $out .= sprintf("            ['codigo' => %s, 'nombre' => %s, 'descripcion' => %s],\n", esc($row['item']), esc($row['nombre']), esc($row['descripcion']));
}
$out .= "        ];\n\n        foreach (\$items as \$item) {\n            Item::firstOrCreate(['codigo' => \$item['codigo']], ['nombre' => \$item['nombre'], 'descripcion' => \$item['descripcion']]);\n        }\n    }\n}\n";
file_put_contents(__DIR__.'/../database/seeders/ItemsSeeder.php', $out);

// --- asignaciones ---
$asignaciones = extractInsertOrIgnoreArray('C:\\laragon\\www\\erp\\database\\seeders\\AsignacionesSeeder.php');
fwrite(STDERR, 'asignaciones: '.count($asignaciones)."\n");

$out = "<?php\n\nnamespace Database\\Seeders;\n\nuse App\\Models\\Item;\nuse Illuminate\\Database\\Seeder;\n\nclass AsignacionesSeeder extends Seeder\n{\n    /**\n     * Seed the real budget classifier asignaciones, resolved to their item by codigo.\n     */\n    public function run(): void\n    {\n        \$asignaciones = [\n";
foreach ($asignaciones as $row) {
    $out .= sprintf("            ['codigo' => %s, 'item' => %s, 'nombre' => %s, 'descripcion' => %s],\n", esc($row['asignacion']), esc($row['item']), esc($row['nombre']), esc($row['descripcion']));
}
$out .= "        ];\n\n        foreach (\$asignaciones as \$asignacion) {\n            \$item = Item::where('codigo', \$asignacion['item'])->firstOrFail();\n\n            \$item->asignaciones()->firstOrCreate(\n                ['codigo' => \$asignacion['codigo']],\n                ['nombre' => \$asignacion['nombre'], 'descripcion' => \$asignacion['descripcion']],\n            );\n        }\n    }\n}\n";
file_put_contents(__DIR__.'/../database/seeders/AsignacionesSeeder.php', $out);

// --- catalogos ---
$catalogos = extractInsertOrIgnoreArray('C:\\laragon\\www\\erp\\database\\seeders\\CatalogosSeeder.php');
fwrite(STDERR, 'catalogos: '.count($catalogos)."\n");

$out = "<?php\n\nnamespace Database\\Seeders;\n\nuse App\\Models\\Item;\nuse Illuminate\\Database\\Seeder;\n\nclass CatalogosSeeder extends Seeder\n{\n    /**\n     * Seed the real budget classifier catalogos (usable accounts), resolved to their item by codigo.\n     */\n    public function run(): void\n    {\n        \$catalogos = [\n";
foreach ($catalogos as $row) {
    $activo = trim((string) $row['estado']) === 'Activo' ? 'true' : 'false';
    $out .= sprintf("            ['codigo' => %s, 'item' => %s, 'nombre' => %s, 'descripcion' => %s, 'activo' => %s],\n", esc($row['catalogo']), esc((string) $row['item']), esc($row['nombre']), esc($row['descripcion']), $activo);
}
$out .= "        ];\n\n        foreach (\$catalogos as \$catalogo) {\n            \$item = Item::where('codigo', \$catalogo['item'])->firstOrFail();\n\n            \$item->catalogos()->firstOrCreate(\n                ['codigo' => \$catalogo['codigo']],\n                ['nombre' => \$catalogo['nombre'], 'descripcion' => \$catalogo['descripcion'], 'activo' => \$catalogo['activo']],\n            );\n        }\n    }\n}\n";
file_put_contents(__DIR__.'/../database/seeders/CatalogosSeeder.php', $out);

fwrite(STDERR, "Done.\n");
