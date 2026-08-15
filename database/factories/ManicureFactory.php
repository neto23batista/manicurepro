<?php

namespace Database\Factories;

use App\Models\Salao;
use Illuminate\Database\Eloquent\Factories\Factory;

class ManicureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'salao_id' => Salao::factory(),
            'nome'     => fake('pt_BR')->name('female'),
            'email'    => fake()->safeEmail(),
            'telefone' => fake('pt_BR')->phoneNumber(),
            'bio'      => fake('pt_BR')->sentence(10),
            'comissao' => fake()->randomElement([35, 40, 42, 45, 50]),
            'ativo'    => true,
        ];
    }
}
