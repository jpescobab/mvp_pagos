<?php

namespace Database\Seeders;

use App\Models\LicitacionMercadoPublico;
use App\Models\LicitacionMercadoPublicoItem;
use Illuminate\Database\Seeder;

/**
 * Snapshot de las licitaciones 2182-*-{L126,LE26} reales, importadas desde
 * Mercado Público el 2026-07-31 vía `adquisiciones:importar-licitaciones-2182`.
 * Existe solo para no golpear la API de Mercado Público en cada
 * `migrate:fresh` durante esta etapa de desarrollo — NO reemplaza la
 * importación real (`LicitacionMercadoPublicoService`), que sigue siendo la
 * única vía de importación en producción.
 */
class LicitacionesMercadoPublicoDesarrolloSeeder extends Seeder
{
    public function run(): void
    {
        $ruta = database_path('seeders/fixtures/licitaciones_mercado_publico_2026.json');

        if (! file_exists($ruta)) {
            return;
        }

        /** @var list<array<string, mixed>> $filas */
        $filas = json_decode((string) file_get_contents($ruta), true) ?? [];

        foreach ($filas as $fila) {
            if (LicitacionMercadoPublico::where('codigo', $fila['codigo'])->exists()) {
                continue;
            }

            $licitacion = LicitacionMercadoPublico::create([
                'codigo' => $fila['codigo'],
                'nombre' => $fila['nombre'],
                'estado_mercado_publico' => $fila['estado_mercado_publico'],
                'codigo_estado_mercado_publico' => $fila['codigo_estado_mercado_publico'],
                'moneda' => $fila['moneda'],
                'monto_estimado' => $fila['monto_estimado'],
                'organismo_comprador' => $fila['organismo_comprador'],
                'cronograma' => $fila['cronograma'],
                'adjudicacion' => $fila['adjudicacion'],
            ]);

            foreach ($fila['items'] ?? [] as $item) {
                LicitacionMercadoPublicoItem::create([
                    'licitacion_mercado_publico_id' => $licitacion->id,
                    'correlativo' => $item['correlativo'],
                    'codigo_producto' => $item['codigo_producto'],
                    'categoria' => $item['categoria'],
                    'nombre_producto' => $item['nombre_producto'],
                    'descripcion' => $item['descripcion'],
                    'unidad_medida' => $item['unidad_medida'],
                    'cantidad' => $item['cantidad'],
                    'adjudicacion' => $item['adjudicacion'],
                ]);
            }
        }
    }
}
