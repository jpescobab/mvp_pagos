<?php

namespace Database\Factories;

use App\Models\OrdenCompraMercadoPublico;
use App\Models\OrdenCompraMercadoPublicoItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrdenCompraMercadoPublicoItem>
 */
class OrdenCompraMercadoPublicoItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cantidad = fake()->numberBetween(1, 20);
        $precioUnitario = fake()->numberBetween(1_000, 100_000);

        return [
            'orden_compra_mercado_publico_id' => OrdenCompraMercadoPublico::factory(),
            'codigo_producto' => fake()->bothify('??-####'),
            'descripcion' => fake()->sentence(4),
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'monto_total' => $cantidad * $precioUnitario,
        ];
    }
}
