<?php

namespace Database\Seeders;

use App\Models\IndicadorEconomico;
use App\Models\IndicadorEconomicoImportacion;
use Illuminate\Database\Seeder;

/**
 * Snapshot de UF/USD/UTM/UTA/IPC reales (CMF), enero-agosto 2026, capturado
 * el 2026-07-30 vía `indicadores:importar-mensual`/`indicadores:importar-usd
 * --periodo=YYYY-MM`. Existe solo para no golpear la API de la CMF en cada
 * `migrate:fresh` durante esta etapa de desarrollo — NO reemplaza la
 * importación real (`ServicioImportacionIndicadores`), que sigue siendo la
 * única vía de importación en producción.
 */
class IndicadoresEconomicosDesarrolloSeeder extends Seeder
{
    public function run(): void
    {
        $ruta = database_path('seeders/fixtures/indicadores_economicos_2026.json');

        if (! file_exists($ruta)) {
            return;
        }

        /** @var list<array<string, mixed>> $filas */
        $filas = json_decode((string) file_get_contents($ruta), true) ?? [];

        if ($filas === []) {
            return;
        }

        $importacion = IndicadorEconomicoImportacion::firstOrCreate(
            ['tipo_importacion' => 'seed_desarrollo'],
            ['estado' => 'success'],
        );

        foreach ($filas as $fila) {
            $condicion = $fila['fecha_valor'] !== null
                ? ['codigo' => $fila['codigo'], 'fecha_valor' => $fila['fecha_valor'], 'fuente' => $fila['fuente'], 'es_proyectado' => false]
                : ['codigo' => $fila['codigo'], 'periodo' => $fila['periodo'], 'fuente' => $fila['fuente'], 'es_proyectado' => false];

            IndicadorEconomico::firstOrCreate($condicion, [
                'importacion_id' => $importacion->id,
                'nombre' => $fila['nombre'],
                'tipo' => $fila['tipo'],
                'valor' => $fila['valor'],
                'periodicidad_valor' => $fila['periodicidad_valor'],
                'periodicidad_publicacion' => $fila['periodicidad_publicacion'],
                'unidad_medida' => $fila['unidad_medida'],
                'moneda_base' => $fila['moneda_base'],
                'requiere_dia_habil' => $fila['requiere_dia_habil'],
                'capturado_en' => now(),
            ]);
        }
    }
}
