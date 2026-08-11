<?php

namespace Database\Factories;

use App\Models\Servico;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServicoVariacaoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'servico_id' => Servico::factory(),
            'nome'       => fake()->randomElement(['Básica', 'Gel', 'Fibra']),
            'preco'      => fake()->randomFloat(2, 30, 120),
            'duracao'    => fake()->randomElement([30, 45, 60, 90]),
            'ordem'      => 0,
            'ativo'      => true,
        ];
    }
}
