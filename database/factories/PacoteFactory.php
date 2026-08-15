<?php

namespace Database\Factories;

use App\Models\Salao;
use Illuminate\Database\Eloquent\Factories\Factory;

class PacoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'salao_id' => Salao::factory(),
            'nome'     => fake()->randomElement([
                'Pacote 5 Sessões',
                'Combo Mensal',
                'Pacote Premium 10x',
            ]),
            'sessoes'       => fake()->randomElement([3, 5, 10]),
            'validade_dias' => fake()->randomElement([30, 60, 90, null]),
            'preco'         => fake()->randomFloat(2, 80, 400),
            'ativo'         => true,
        ];
    }
}
