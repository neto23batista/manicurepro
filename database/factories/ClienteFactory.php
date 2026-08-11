<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Salao;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'salao_id'         => Salao::factory(),
            'user_id'          => null,
            'nome'             => fake('pt_BR')->name('female'),
            'email'            => fake()->unique()->safeEmail(),
            'telefone'         => fake('pt_BR')->phoneNumber(),
            'cpf'              => fake('pt_BR')->cpf(false),
            'data_nascimento'  => fake()->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
            'total_visitas'    => 0,
            'total_gasto'      => 0,
            'pontos_fidelidade'=> 0,
            'total_faltas'     => 0,
            'ativo'            => true,
        ];
    }
}
