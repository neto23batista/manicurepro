<?php

namespace Database\Factories;

use App\Models\Manicure;
use App\Models\Salao;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgendamentoFactory extends Factory
{
    public function definition(): array
    {
        $inicio = Carbon::now()->addDays(fake()->numberBetween(1, 10))
            ->setHour(fake()->numberBetween(8, 16))->setMinute(0)->setSecond(0);

        return [
            'salao_id' => Salao::factory(),
            'manicure_id' => Manicure::factory(),
            'data_hora_inicio' => $inicio,
            'data_hora_fim' => $inicio->copy()->addMinutes(30),
            'status' => 'aguardando',
            'origem' => 'web',
            'valor_total' => fake()->randomElement([25, 35, 45, 55, 80]),
            'valor_desconto' => 0,
            'nome_cliente' => fake('pt_BR')->name('female'),
        ];
    }
}
