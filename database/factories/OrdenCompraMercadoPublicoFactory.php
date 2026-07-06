<?php

namespace Database\Factories;

use App\Models\OrdenCompraMercadoPublico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrdenCompraMercadoPublico>
 */
class OrdenCompraMercadoPublicoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $montoNeto = fake()->numberBetween(100_000, 5_000_000);

        return [
            'codigo' => fake()->unique()->numerify('####-##-LE##'),
            'estado_mercado_publico' => 'Aceptada',
            'moneda' => 'CLP',
            'forma_pago' => 'Transferencia',
            'plazo_entrega_dias' => fake()->numberBetween(5, 60),
            'monto_neto' => $montoNeto,
            'monto_total' => (int) round($montoNeto * 1.19),
            'fecha_emision' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'organismo_comprador' => [
                'nombre' => 'Corporación Administrativa del Poder Judicial',
                'unidad' => fake()->city(),
                'rut' => '60.503.000-9',
            ],
            'cronograma' => [
                ['estado' => 'Enviada', 'fecha' => fake()->dateTimeBetween('-6 months', '-1 month')->format('Y-m-d')],
                ['estado' => 'Aceptada', 'fecha' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d')],
            ],
        ];
    }
}
