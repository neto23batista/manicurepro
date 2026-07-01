<?php

namespace Database\Factories;

use App\Models\Salao;
use Illuminate\Database\Eloquent\Factories\Factory;

class CupomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'salao_id'        => Salao::factory(),
            'codigo'          => strtoupper(fake()->bothify('???###')),
            'tipo'            => fake()->randomElement(['percentual', 'fixo']),
            'valor'           => fake()->randomFloat(2, 5, 50),
            'minimo_pedido'   => 0,
            'maximo_desconto' => null,
            'uso_maximo'      => fake()->optional()->numberBetween(10, 100),
            'uso_atual'       => 0,
            'validade'        => now()->addMonths(2),
            'ativo'           => true,
        ];
    }
}
