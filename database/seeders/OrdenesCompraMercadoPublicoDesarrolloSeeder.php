<?php

namespace Database\Seeders;

use App\Models\OrdenCompraMercadoPublico;
use App\Models\OrdenCompraMercadoPublicoItem;
use App\Models\Proveedor;
use Illuminate\Database\Seeder;

/**
 * Snapshot de las órdenes de compra 2182-*-{CM26,AG26,SE26} reales, importadas
 * desde Mercado Público el 2026-07-31 vía `adquisiciones:importar-oc-2182`.
 * Existe solo para no golpear la API de Mercado Público en cada
 * `migrate:fresh` durante esta etapa de desarrollo — NO reemplaza la
 * importación real (`OrdenCompraMercadoPublicoService`), que sigue siendo la
 * única vía de importación en producción.
 */
class OrdenesCompraMercadoPublicoDesarrolloSeeder extends Seeder
{
    public function run(): void
    {
        $ruta = database_path('seeders/fixtures/ordenes_compra_mercado_publico_2026.json');

        if (! file_exists($ruta)) {
            return;
        }

        /** @var list<array<string, mixed>> $filas */
        $filas = json_decode((string) file_get_contents($ruta), true) ?? [];

        foreach ($filas as $fila) {
            if (OrdenCompraMercadoPublico::where('codigo', $fila['codigo'])->exists()) {
                continue;
            }

            $proveedor = $this->resolverProveedor($fila['proveedor'] ?? null);

            $oc = OrdenCompraMercadoPublico::create([
                'codigo' => $fila['codigo'],
                'proveedor_id' => $proveedor?->id,
                'estado_mercado_publico' => $fila['estado_mercado_publico'],
                'moneda' => $fila['moneda'],
                'forma_pago' => $fila['forma_pago'],
                'plazo_entrega_dias' => $fila['plazo_entrega_dias'],
                'monto_neto' => $fila['monto_neto'],
                'monto_total' => $fila['monto_total'],
                'fecha_emision' => $fila['fecha_emision'],
                'organismo_comprador' => $fila['organismo_comprador'],
                'cronograma' => $fila['cronograma'],
            ]);

            foreach ($fila['items'] ?? [] as $item) {
                OrdenCompraMercadoPublicoItem::create([
                    'orden_compra_mercado_publico_id' => $oc->id,
                    'codigo_producto' => $item['codigo_producto'],
                    'descripcion' => $item['descripcion'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'monto_total' => $item['monto_total'],
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>|null  $datosProveedor
     */
    private function resolverProveedor(?array $datosProveedor): ?Proveedor
    {
        if ($datosProveedor === null || ($datosProveedor['rutproveedor'] ?? '') === '') {
            return null;
        }

        return Proveedor::firstOrCreate(
            ['rutproveedor' => Proveedor::normalizarRut($datosProveedor['rutproveedor'])],
            [
                'nombre' => $datosProveedor['nombre'] ?? '',
                'estado' => Proveedor::ESTADO_ACTIVO,
                'direccion' => $datosProveedor['direccion'] ?? null,
                'comuna' => $datosProveedor['comuna'] ?? null,
                'region' => $datosProveedor['region'] ?? null,
                'giro' => $datosProveedor['giro'] ?? null,
                'correo' => $datosProveedor['correo'] ?? null,
                'contacto' => $datosProveedor['contacto'] ?? null,
                'contacto_cargo' => $datosProveedor['contacto_cargo'] ?? null,
                'contacto_telefono' => $datosProveedor['contacto_telefono'] ?? null,
            ],
        );
    }
}
