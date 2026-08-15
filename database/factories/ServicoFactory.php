<?php

namespace Database\Factories;

use App\Models\Salao;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServicoFactory extends Factory
{
    public function definition(): array
    {
        $servicos = [
            ['nome' => 'Manicure Simples', 'preco' => 25, 'duracao' => 30],
            ['nome' => 'Pedicure', 'preco' => 35, 'duracao' => 45],
            ['nome' => 'Gel', 'preco' => 45, 'duracao' => 60],
            ['nome' => 'Acrílico', 'preco' => 80, 'duracao' => 90],
        ];
        $s = fake()->randomElement($servicos);

        return [
            'salao_id'          => Salao::factory(),
            'nome'              => $s['nome'],
            'preco'             => $s['preco'],
            'duracao'           => $s['duracao'],
            'combo'             => false,
            'disponivel_online' => true,
            'ativo'             => true,
        ];
    }
}
