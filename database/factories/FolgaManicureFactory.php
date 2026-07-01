<?php

namespace Database\Factories;

use App\Models\FolgaManicure;
use App\Models\Manicure;
use Illuminate\Database\Eloquent\Factories\Factory;

class FolgaManicureFactory extends Factory
{
    protected $model = FolgaManicure::class;

    public function definition(): array
    {
        return [
            'manicure_id' => Manicure::factory(),
            'data'        => now()->addDays(fake()->numberBetween(1, 60))->toDateString(),
            'motivo'      => fake()->randomElement(['Médico', 'Viagem', 'Pessoal']),
            'dia_todo'    => true,
            'hora_inicio' => null,
            'hora_fim'    => null,
        ];
    }
}
