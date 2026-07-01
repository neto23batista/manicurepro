<?php

namespace Database\Seeders;

use App\Models\Agendamento;
use App\Models\CategoriaServico;
use App\Models\Cliente;
use App\Models\ConfiguracaoSalao;
use App\Models\DisponibilidadeManicure;
use App\Models\HorarioFuncionamento;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // =====================
        // ADMIN (gestão do sistema)
        // =====================
        User::create([
            'name' => 'Administradora',
            'email' => 'admin@fernandasilvanails.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'ativo' => true,
        ]);

        // =====================
        // SALÃO ÚNICO
        // =====================
        $salao = Salao::create([
            'nome' => 'Fernanda Silva Nails',
            'slug' => 'fernanda-silva-nails',
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
            'ativo' => true,
        ]);

        // Configuração do salão
        ConfiguracaoSalao::create([
            'salao_id' => $salao->id,
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
        ]);

        // Horários de funcionamento (0=Dom ... 6=Sáb)
        $horariosConfig = [
            0 => ['abertura' => '09:00:00', 'fechamento' => '17:00:00', 'ativo' => false],
            1 => ['abertura' => '09:00:00', 'fechamento' => '19:00:00', 'ativo' => true],
            2 => ['abertura' => '09:00:00', 'fechamento' => '19:00:00', 'ativo' => true],
            3 => ['abertura' => '09:00:00', 'fechamento' => '19:00:00', 'ativo' => true],
            4 => ['abertura' => '09:00:00', 'fechamento' => '19:00:00', 'ativo' => true],
            5 => ['abertura' => '09:00:00', 'fechamento' => '19:00:00', 'ativo' => true],
            6 => ['abertura' => '09:00:00', 'fechamento' => '15:00:00', 'ativo' => true],
        ];

        foreach ($horariosConfig as $dia => $config) {
            HorarioFuncionamento::create([
                'salao_id' => $salao->id,
                'dia_semana' => $dia,
                'hora_abertura' => $config['abertura'],
                'hora_fechamento' => $config['fechamento'],
                'ativo' => $config['ativo'],
            ]);
        }

        // =====================
        // CATEGORIAS
        // =====================
        $catManicure = CategoriaServico::create([
            'salao_id' => $salao->id, 'nome' => 'Manicure', 'cor' => '#e91e8c',
            'icone' => 'fa-hand-sparkles', 'ordem' => 1, 'ativo' => true,
        ]);
        $catPedicure = CategoriaServico::create([
            'salao_id' => $salao->id, 'nome' => 'Pedicure', 'cor' => '#9c27b0',
            'icone' => 'fa-spa', 'ordem' => 2, 'ativo' => true,
        ]);
        $catEspecial = CategoriaServico::create([
            'salao_id' => $salao->id, 'nome' => 'Tratamentos Especiais', 'cor' => '#e91e8c',
            'icone' => 'fa-star', 'ordem' => 3, 'ativo' => true,
        ]);
        $catCombos = CategoriaServico::create([
            'salao_id' => $salao->id, 'nome' => 'Combos', 'cor' => '#ff6b9d',
            'icone' => 'fa-gift', 'ordem' => 4, 'ativo' => true,
        ]);

        // =====================
        // SERVIÇOS
        // =====================
        $servicos = [
            ['nome' => 'Manicure Simples', 'categoria_id' => $catManicure->id, 'preco' => 30.00, 'duracao' => 30, 'combo' => false],
            ['nome' => 'Pedicure', 'categoria_id' => $catPedicure->id, 'preco' => 40.00, 'duracao' => 45, 'combo' => false],
            ['nome' => 'Manicure + Pedicure', 'categoria_id' => $catCombos->id, 'preco' => 65.00, 'duracao' => 75, 'combo' => true],
            ['nome' => 'Esmaltação em Gel', 'categoria_id' => $catEspecial->id, 'preco' => 55.00, 'duracao' => 60, 'combo' => false],
            ['nome' => 'Alongamento em Fibra', 'categoria_id' => $catEspecial->id, 'preco' => 120.00, 'duracao' => 120, 'combo' => false],
            ['nome' => 'Alongamento em Gel', 'categoria_id' => $catEspecial->id, 'preco' => 130.00, 'duracao' => 120, 'combo' => false],
            ['nome' => 'Nail Art (por unha)', 'categoria_id' => $catEspecial->id, 'preco' => 10.00, 'duracao' => 30, 'combo' => false],
            ['nome' => 'Remoção de Gel/Alongamento', 'categoria_id' => $catManicure->id, 'preco' => 35.00, 'duracao' => 30, 'combo' => false],
            ['nome' => 'Spa dos Pés', 'categoria_id' => $catPedicure->id, 'preco' => 55.00, 'duracao' => 60, 'combo' => false],
            ['nome' => 'Blindagem', 'categoria_id' => $catEspecial->id, 'preco' => 45.00, 'duracao' => 45, 'combo' => false],
            ['nome' => 'Combo Premium (Mãos + Pés + Spa)', 'categoria_id' => $catCombos->id, 'preco' => 150.00, 'duracao' => 120, 'combo' => true],
        ];

        $servicoIds = [];
        foreach ($servicos as $s) {
            $servico = Servico::create(array_merge($s, [
                'salao_id' => $salao->id,
                'disponivel_online' => true,
                'ativo' => true,
            ]));
            $servicoIds[] = $servico->id;
        }

        // =====================
        // MANICURES (profissionais)
        // =====================
        $manicuresData = [
            ['nome' => 'Fernanda Silva', 'email' => 'fernanda.profissional@fernandasilvanails.com', 'telefone' => '(11) 99999-1234', 'comissao' => 100, 'bio' => 'Nail designer e proprietária do estúdio, com mais de 8 anos de experiência em alongamentos e nail art.'],
        ];

        $manicures = [];
        foreach ($manicuresData as $md) {
            $user = User::create([
                'name' => $md['nome'],
                'email' => $md['email'],
                'password' => Hash::make('manicure123'),
                'role' => 'manicure',
                'salao_id' => $salao->id,
                'phone' => $md['telefone'],
                'ativo' => true,
            ]);

            $manicure = Manicure::create([
                'user_id' => $user->id,
                'salao_id' => $salao->id,
                'nome' => $md['nome'],
                'email' => $md['email'],
                'telefone' => $md['telefone'],
                'comissao' => $md['comissao'],
                'bio' => $md['bio'],
                'ativo' => true,
            ]);

            // Disponibilidade: Seg a Sáb
            for ($dia = 1; $dia <= 6; $dia++) {
                DisponibilidadeManicure::create([
                    'manicure_id' => $manicure->id,
                    'dia_semana' => $dia,
                    'hora_inicio' => '09:00:00',
                    'hora_fim' => '19:00:00',
                    'ativo' => true,
                ]);
            }

            $manicures[] = $manicure;
        }

        // =====================
        // DONO (Fernanda — proprietária/gestora)
        // =====================
        User::create([
            'name' => 'Fernanda Silva',
            'email' => 'fernanda@fernandasilvanails.com',
            'password' => Hash::make('dono123'),
            'role' => 'dono',
            'salao_id' => $salao->id,
            'phone' => '(11) 99999-1234',
            'ativo' => true,
        ]);

        // =====================
        // CLIENTES
        // =====================
        $clienteUser = User::create([
            'name' => 'Maria Cliente',
            'email' => 'cliente@fernandasilvanails.com',
            'password' => Hash::make('cliente123'),
            'role' => 'cliente',
            'salao_id' => $salao->id,
            'ativo' => true,
        ]);

        $cliente = Cliente::create([
            'user_id' => $clienteUser->id,
            'salao_id' => $salao->id,
            'nome' => 'Maria Cliente',
            'email' => 'cliente@fernandasilvanails.com',
            'telefone' => '(11) 97777-0001',
            'data_nascimento' => '1990-05-15',
            'ativo' => true,
        ]);

        // Clientes adicionais (para histórico/relatórios)
        $nomesClientes = [
            'Juliana Rodrigues', 'Patricia Lima', 'Fernanda Costa', 'Marina Dias',
            'Aline Mendes', 'Vanessa Pereira', 'Beatriz Alves', 'Larissa Ferreira',
        ];

        $clientesAdicionais = [];
        foreach ($nomesClientes as $nome) {
            $c = Cliente::create([
                'salao_id' => $salao->id,
                'nome' => $nome,
                'email' => strtolower(str_replace(' ', '.', $nome)) . '@email.com',
                'telefone' => '(11) 9' . rand(7000, 9999) . '-' . rand(1000, 9999),
                'ativo' => true,
            ]);
            $clientesAdicionais[] = $c;
        }

        // =====================
        // AGENDAMENTOS (histórico dos últimos 30 dias + futuros)
        // =====================
        $statusOptions = ['concluido', 'concluido', 'concluido', 'cancelado', 'nao_compareceu'];
        $todosClientes = array_merge([$cliente], $clientesAdicionais);

        // Passados (últimos 30 dias)
        for ($i = 30; $i >= 1; $i--) {
            $data = now()->subDays($i);
            $diaSemana = (int) $data->format('w');
            if ($diaSemana === 0) continue; // Pula domingo

            $qtd = rand(2, 5);

            for ($j = 0; $j < $qtd; $j++) {
                $manicure = $manicures[array_rand($manicures)];
                $servicoId = $servicoIds[array_rand($servicoIds)];
                $servico = Servico::find($servicoId);
                $clienteAg = $todosClientes[array_rand($todosClientes)];

                $hora = rand(9, 16);
                $inicio = $data->copy()->setHour($hora)->setMinute(0)->setSecond(0);
                $fim = $inicio->copy()->addMinutes($servico->duracao);
                $status = $statusOptions[array_rand($statusOptions)];

                $ag = Agendamento::create([
                    'salao_id' => $salao->id,
                    'cliente_id' => $clienteAg->id,
                    'manicure_id' => $manicure->id,
                    'data_hora_inicio' => $inicio,
                    'data_hora_fim' => $fim,
                    'status' => $status,
                    'valor_total' => $servico->preco,
                    'valor_desconto' => 0,
                    'origem' => 'web',
                ]);

                $ag->servicos()->attach($servico->id, [
                    'preco' => $servico->preco,
                    'duracao' => $servico->duracao,
                ]);

                if ($status === 'concluido') {
                    $clienteAg->increment('total_visitas');
                    $clienteAg->increment('total_gasto', $servico->preco);
                }
            }
        }

        // Futuros (próximos 7 dias)
        for ($i = 1; $i <= 7; $i++) {
            $data = now()->addDays($i);
            $diaSemana = (int) $data->format('w');
            if ($diaSemana === 0) continue;

            $qtd = rand(1, 3);
            for ($j = 0; $j < $qtd; $j++) {
                $manicure = $manicures[array_rand($manicures)];
                $servicoId = $servicoIds[array_rand($servicoIds)];
                $servico = Servico::find($servicoId);
                $clienteAg = $todosClientes[array_rand($todosClientes)];

                $hora = rand(9, 16);
                $inicio = $data->copy()->setHour($hora)->setMinute(0)->setSecond(0);
                $fim = $inicio->copy()->addMinutes($servico->duracao);

                $ag = Agendamento::create([
                    'salao_id' => $salao->id,
                    'cliente_id' => $clienteAg->id,
                    'manicure_id' => $manicure->id,
                    'data_hora_inicio' => $inicio,
                    'data_hora_fim' => $fim,
                    'status' => 'confirmado',
                    'valor_total' => $servico->preco,
                    'valor_desconto' => 0,
                    'origem' => 'web',
                ]);

                $ag->servicos()->attach($servico->id, [
                    'preco' => $servico->preco,
                    'duracao' => $servico->duracao,
                ]);
            }
        }

        $this->command->info('');
        $this->command->info('✅ Fernanda Silva Nails — Dados de Teste Criados!');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('👑 Admin:    admin@fernandasilvanails.com      / admin123');
        $this->command->info('🏪 Dono:     fernanda@fernandasilvanails.com   / dono123');
        $this->command->info('💅 Manicure: fernanda.profissional@fernandasilvanails.com / manicure123');
        $this->command->info('👤 Cliente:  cliente@fernandasilvanails.com    / cliente123');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('🌸 Site público: / (a home é a página do salão)');
        $this->command->info('');
    }
}
