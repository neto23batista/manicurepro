<?php

namespace Database\Factories;

use App\Models\Folga;
use App\Models\Salao;
use Illuminate\Database\Eloquent\Factories\Factory;

class FolgaFactory extends Factory
{
    protected $model = Folga::class;

    public function definition(): array
    {
        return [
            'salao_id'    => Salao::factory(),
            'data'        => now()->addDays(fake()->numberBetween(1, 60))->toDateString(),
            'motivo'      => fake()->randomElement(['Feriado', 'Manutenção', 'Reforma', 'Treinamento']),
            'dia_todo'    => true,
            'hora_inicio' => null,
            'hora_fim'    => null,
        ];
    }
}
