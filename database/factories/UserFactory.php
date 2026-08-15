<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'              => fake('pt_BR')->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => bcrypt('password'),
            'role'              => 'cliente',
            'phone'             => fake('pt_BR')->phoneNumber(),
            'ativo'             => true,
            'remember_token'    => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }

    public function dono(): static
    {
        return $this->state(['role' => 'dono']);
    }

    public function manicure(): static
    {
        return $this->state(['role' => 'manicure']);
    }

    public function cliente(): static
    {
        return $this->state(['role' => 'cliente']);
    }

    public function inativo(): static
    {
        return $this->state(['ativo' => false]);
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }
}
