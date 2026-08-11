<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoSalaoSeeder::class,
            DemoEquipeSeeder::class,
            DemoCatalogoSeeder::class,
            DemoClientesSeeder::class,
            DemoAgendamentosSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('Fernanda Silva Nails — demo pronta (idempotente).');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('Admin:     admin@fernandasilvanails.com                 / admin123');
        $this->command->info('Dono:      fernanda@fernandasilvanails.com              / dono123');
        $this->command->info('Atendente: atendente@fernandasilvanails.com             / atendente123');
        $this->command->info('Manicure:  fernanda.profissional@fernandasilvanails.com / manicure123');
        $this->command->info('           camila@fernandasilvanails.com                / manicure123');
        $this->command->info('           juliana@fernandasilvanails.com               / manicure123');
        $this->command->info('Cliente:   cliente@fernandasilvanails.com               / cliente123');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('Cupons: BEMVINDA | FIDELIDADE10 | DESCONTO20 | ANIVERSARIO');
        $this->command->info('Site público: / (slug fernanda-silva-nails)');
        $this->command->info('');
    }
}
