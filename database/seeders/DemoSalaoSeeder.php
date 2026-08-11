<?php

namespace Database\Seeders;

use App\Models\ConfiguracaoSalao;
use App\Models\HorarioFuncionamento;
use App\Models\Salao;
use Illuminate\Database\Seeder;

class DemoSalaoSeeder extends Seeder
{
    public const SLUG = 'fernanda-silva-nails';

    public function run(): void
    {
        $salao = Salao::updateOrCreate(
            ['slug' => self::SLUG],
            [
                'nome' => 'Fernanda Silva Nails',
                'descricao' => 'Estúdio de unhas da Fernanda Silva — cuidado, capricho e acabamento impecável. Agende seu horário online.',
                'endereco' => 'Av. das Acácias',
                'numero' => '450',
                'bairro' => 'Centro',
                'cidade' => 'São Paulo',
                'estado' => 'SP',
                'cep' => '01310-100',
                'telefone' => '(11) 3333-4444',
                'whatsapp' => '(11) 99999-1234',
                'email' => 'contato@fernandasilvanails.com',
                'instagram' => 'fernandasilvanails',
                'latitude' => -23.5505,
                'longitude' => -46.6333,
                'ativo' => true,
            ]
        );

        ConfiguracaoSalao::updateOrCreate(
            ['salao_id' => $salao->id],
            [
                'cor_primaria' => '#e91e8c',
                'permitir_agendamento_online' => true,
                'intervalo_agendamento' => 30,
                'antecedencia_minima' => 1,
                'antecedencia_maxima' => 30,
                'cancelamento_prazo' => 2,
                'fidelidade_ativo' => true,
                'pontos_por_real' => 1,
                'pontos_para_desconto' => 100,
                'valor_desconto_pontos' => 10.00,
                'notificar_email' => true,
                'lembrete_horas' => 24,
            ]
        );

        // 0=Dom … 6=Sáb
        $horarios = [
            0 => ['abertura' => '09:00:00', 'fechamento' => '17:00:00', 'ativo' => false],
            1 => ['abertura' => '09:00:00', 'fechamento' => '19:00:00', 'ativo' => true],
            2 => ['abertura' => '09:00:00', 'fechamento' => '19:00:00', 'ativo' => true],
            3 => ['abertura' => '09:00:00', 'fechamento' => '19:00:00', 'ativo' => true],
            4 => ['abertura' => '09:00:00', 'fechamento' => '19:00:00', 'ativo' => true],
            5 => ['abertura' => '09:00:00', 'fechamento' => '19:00:00', 'ativo' => true],
            6 => ['abertura' => '09:00:00', 'fechamento' => '15:00:00', 'ativo' => true],
        ];

        foreach ($horarios as $dia => $config) {
            HorarioFuncionamento::updateOrCreate(
                ['salao_id' => $salao->id, 'dia_semana' => $dia],
                [
                    'hora_abertura' => $config['abertura'],
                    'hora_fechamento' => $config['fechamento'],
                    'ativo' => $config['ativo'],
                ]
            );
        }
    }
}
