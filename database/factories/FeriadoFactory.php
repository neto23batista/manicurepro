<?php

namespace Database\Factories;

use App\Models\Feriado;
use App\Models\Salao;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeriadoFactory extends Factory
{
    protected $model = Feriado::class;

    public function definition(): array
    {
        return [
            'salao_id'    => Salao::factory(),
            'nome'        => fake()->randomElement(['Natal', 'Ano Novo', 'Tiradentes', 'Independência']),
            'mes'         => fake()->numberBetween(1, 12),
            'dia'         => fake()->numberBetween(1, 28),
            'dia_todo'    => true,
            'hora_inicio' => null,
            'hora_fim'    => null,
            'ativo'       => true,
        ];
    }
}
