<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Salao;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoClientesSeeder extends Seeder
{
    public function run(): void
    {
        $salao = Salao::where('slug', DemoSalaoSeeder::SLUG)->firstOrFail();

        $user = User::firstOrCreate(
            ['email' => 'cliente@fernandasilvanails.com'],
            [
                'name'              => 'Maria Cliente',
                'password'          => 'cliente123',
                'role'              => 'cliente',
                'salao_id'          => $salao->id,
                'ativo'             => true,
                'email_verified_at' => now(),
            ],
        );

        $user->fill([
            'name'              => 'Maria Cliente',
            'role'              => 'cliente',
            'salao_id'          => $salao->id,
            'ativo'             => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        Cliente::updateOrCreate(
            ['salao_id' => $salao->id, 'email' => 'cliente@fernandasilvanails.com'],
            [
                'user_id'         => $user->id,
                'nome'            => 'Maria Cliente',
                'telefone'        => '(11) 97777-0001',
                'data_nascimento' => '1990-05-15',
                'ativo'           => true,
            ],
        );

        $extras = [
            ['nome' => 'Juliana Rodrigues', 'email' => 'juliana.rodrigues@email.com', 'telefone' => '(11) 97001-1001', 'data_nascimento' => '1988-03-12'],
            ['nome' => 'Patricia Lima', 'email' => 'patricia.lima@email.com', 'telefone' => '(11) 97002-1002', 'data_nascimento' => '1992-07-22'],
            ['nome' => 'Fernanda Costa', 'email' => 'fernanda.costa@email.com', 'telefone' => '(11) 97003-1003', 'data_nascimento' => '1995-11-05'],
            ['nome' => 'Marina Dias', 'email' => 'marina.dias@email.com', 'telefone' => '(11) 97004-1004', 'data_nascimento' => '1987-01-30'],
            ['nome' => 'Aline Mendes', 'email' => 'aline.mendes@email.com', 'telefone' => '(11) 97005-1005', 'data_nascimento' => '1993-09-18'],
            ['nome' => 'Vanessa Pereira', 'email' => 'vanessa.pereira@email.com', 'telefone' => '(11) 97006-1006', 'data_nascimento' => '1991-04-08'],
            ['nome' => 'Beatriz Alves', 'email' => 'beatriz.alves@email.com', 'telefone' => '(11) 97007-1007', 'data_nascimento' => '1996-12-14'],
            ['nome' => 'Larissa Ferreira', 'email' => 'larissa.ferreira@email.com', 'telefone' => '(11) 97008-1008', 'data_nascimento' => '1989-06-25'],
        ];

        foreach ($extras as $c) {
            Cliente::updateOrCreate(
                ['salao_id' => $salao->id, 'email' => $c['email']],
                array_merge($c, ['ativo' => true]),
            );
        }
    }
}
