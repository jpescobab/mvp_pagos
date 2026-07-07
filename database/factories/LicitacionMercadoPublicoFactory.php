<?php

namespace Database\Factories;

use App\Models\LicitacionMercadoPublico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LicitacionMercadoPublico>
 */
class LicitacionMercadoPublicoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->numerify('####-##-LE##'),
            'nombre' => fake()->sentence(4),
            'estado_mercado_publico' => 'Publicada',
            'codigo_estado_mercado_publico' => 5,
            'moneda' => 'CLP',
            'monto_estimado' => fake()->numberBetween(500_000, 20_000_000),
            'organismo_comprador' => [
                'nombre' => 'Corporación Administrativa del Poder Judicial',
                'unidad' => fake()->city(),
                'rut' => '60.503.000-9',
            ],
            'cronograma' => [
                ['estado' => 'Creada', 'fecha' => fake()->dateTimeBetween('-3 months', '-2 months')->format('Y-m-d H:i:s')],
                ['estado' => 'Publicada', 'fecha' => fake()->dateTimeBetween('-2 months', '-1 month')->format('Y-m-d H:i:s')],
            ],
            'adjudicacion' => null,
        ];
    }
}
