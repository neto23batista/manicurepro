<?php

namespace Database\Factories;

use App\Models\Salao;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoriaServicoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'salao_id'  => Salao::factory(),
            'nome'      => fake()->randomElement(['Manicure', 'Pedicure', 'Tratamentos', 'Spa', 'Nail Art']).' '.fake()->randomNumber(3),
            'descricao' => fake()->optional()->sentence(),
            'cor'       => fake()->hexColor(),
            'icone'     => 'fa-spa',
            'ordem'     => fake()->numberBetween(0, 10),
            'ativo'     => true,
        ];
    }
}
