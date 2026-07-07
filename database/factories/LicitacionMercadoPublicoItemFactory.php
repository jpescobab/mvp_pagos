<?php

namespace Database\Factories;

use App\Models\LicitacionMercadoPublico;
use App\Models\LicitacionMercadoPublicoItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LicitacionMercadoPublicoItem>
 */
class LicitacionMercadoPublicoItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'licitacion_mercado_publico_id' => LicitacionMercadoPublico::factory(),
            'correlativo' => fake()->numberBetween(1, 5),
            'codigo_producto' => fake()->numerify('########'),
            'categoria' => fake()->words(3, true),
            'nombre_producto' => fake()->words(3, true),
            'descripcion' => fake()->sentence(6),
            'unidad_medida' => 'Unidad',
            'cantidad' => fake()->numberBetween(1, 20),
            'adjudicacion' => null,
        ];
    }
}
