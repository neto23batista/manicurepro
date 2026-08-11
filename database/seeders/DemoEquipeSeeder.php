<?php

namespace Database\Seeders;

use App\Models\DisponibilidadeManicure;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoEquipeSeeder extends Seeder
{
    public function run(): void
    {
        $salao = Salao::where('slug', DemoSalaoSeeder::SLUG)->firstOrFail();

        $this->upsertUser([
            'email' => 'admin@fernandasilvanails.com',
            'name' => 'Administradora',
            'password' => 'admin123',
            'role' => 'admin',
            'salao_id' => null,
            'phone' => null,
        ]);

        $this->upsertUser([
            'email' => 'fernanda@fernandasilvanails.com',
            'name' => 'Fernanda Silva',
            'password' => 'dono123',
            'role' => 'dono',
            'salao_id' => $salao->id,
            'phone' => '(11) 99999-1234',
        ]);

        $this->upsertUser([
            'email' => 'atendente@fernandasilvanails.com',
            'name' => 'Ana Atendente',
            'password' => 'atendente123',
            'role' => 'atendente',
            'salao_id' => $salao->id,
            'phone' => '(11) 98888-0001',
        ]);

        $manicures = [
            [
                'nome' => 'Fernanda Silva',
                'email' => 'fernanda.profissional@fernandasilvanails.com',
                'telefone' => '(11) 99999-1234',
                'comissao' => 100,
                'bio' => 'Nail designer e proprietária do estúdio, com mais de 8 anos de experiência em alongamentos e nail art.',
                'especialidades' => ['Alongamento', 'Nail Art', 'Gel'],
                'horarios' => [
                    1 => ['09:00:00', '19:00:00'],
                    2 => ['09:00:00', '19:00:00'],
                    3 => ['09:00:00', '19:00:00'],
                    4 => ['09:00:00', '19:00:00'],
                    5 => ['09:00:00', '19:00:00'],
                    6 => ['09:00:00', '15:00:00'],
                ],
            ],
            [
                'nome' => 'Camila Santos',
                'email' => 'camila@fernandasilvanails.com',
                'telefone' => '(11) 98888-2222',
                'comissao' => 50,
                'bio' => 'Especialista em manicure clássica, pedicure e spa dos pés.',
                'especialidades' => ['Manicure', 'Pedicure', 'Spa'],
                'horarios' => [
                    1 => ['09:00:00', '18:00:00'],
                    2 => ['09:00:00', '18:00:00'],
                    3 => ['09:00:00', '18:00:00'],
                    4 => ['09:00:00', '18:00:00'],
                    5 => ['09:00:00', '18:00:00'],
                    // Folga aos sábados
                ],
            ],
            [
                'nome' => 'Juliana Oliveira',
                'email' => 'juliana@fernandasilvanails.com',
                'telefone' => '(11) 97777-3333',
                'comissao' => 45,
                'bio' => 'Focada em esmaltação em gel e blindagem com acabamento duradouro.',
                'especialidades' => ['Gel', 'Blindagem', 'Fibra'],
                'horarios' => [
                    2 => ['10:00:00', '19:00:00'],
                    3 => ['10:00:00', '19:00:00'],
                    4 => ['10:00:00', '19:00:00'],
                    5 => ['10:00:00', '19:00:00'],
                    6 => ['09:00:00', '15:00:00'],
                ],
            ],
        ];

        foreach ($manicures as $md) {
            $user = $this->upsertUser([
                'email' => $md['email'],
                'name' => $md['nome'],
                'password' => 'manicure123',
                'role' => 'manicure',
                'salao_id' => $salao->id,
                'phone' => $md['telefone'],
            ]);

            $manicure = Manicure::updateOrCreate(
                ['salao_id' => $salao->id, 'email' => $md['email']],
                [
                    'user_id' => $user->id,
                    'nome' => $md['nome'],
                    'telefone' => $md['telefone'],
                    'comissao' => $md['comissao'],
                    'bio' => $md['bio'],
                    'especialidades' => $md['especialidades'],
                    'ativo' => true,
                ]
            );

            foreach ($md['horarios'] as $dia => [$inicio, $fim]) {
                DisponibilidadeManicure::updateOrCreate(
                    ['manicure_id' => $manicure->id, 'dia_semana' => $dia],
                    [
                        'hora_inicio' => $inicio,
                        'hora_fim' => $fim,
                        'ativo' => true,
                    ]
                );
            }

            // Desativa dias que não estão na grade (idempotente em re-seed)
            DisponibilidadeManicure::where('manicure_id', $manicure->id)
                ->whereNotIn('dia_semana', array_keys($md['horarios']))
                ->update(['ativo' => false]);
        }
    }

    /**
     * @param  array{email:string,name:string,password:string,role:string,salao_id:?int,phone:?string}  $data
     */
    private function upsertUser(array $data): User
    {
        $user = User::firstOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'password' => $data['password'],
                'role' => $data['role'],
                'salao_id' => $data['salao_id'],
                'phone' => $data['phone'],
                'ativo' => true,
                'email_verified_at' => now(),
            ]
        );

        $user->fill([
            'name' => $data['name'],
            'role' => $data['role'],
            'salao_id' => $data['salao_id'],
            'phone' => $data['phone'],
            'ativo' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return $user;
    }
}
