<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SalaoFactory extends Factory
{
    public function definition(): array
    {
        $nome = fake('pt_BR')->company() . ' Nail Art';
        return [
            'nome' => $nome,
            'slug' => Str::slug($nome) . '-' . Str::random(4),
            'descricao' => fake('pt_BR')->sentence(12),
            'endereco' => fake('pt_BR')->streetName(),
            'numero' => (string) fake()->numberBetween(1, 999),
            'bairro' => fake('pt_BR')->citySuffix(),
            'cidade' => fake('pt_BR')->city(),
            'estado' => fake('pt_BR')->stateAbbr(),
            'cep' => fake('pt_BR')->postcode(),
            'telefone' => fake('pt_BR')->phoneNumber(),
            'whatsapp' => fake('pt_BR')->phoneNumber(),
            'email' => fake()->safeEmail(),
            'ativo' => true,
        ];
    }
}
