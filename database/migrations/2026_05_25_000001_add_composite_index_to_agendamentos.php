<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índice composto que acelera as queries mais frequentes do AgendaService:
 *
 *   - Listar agendamentos de uma manicure em uma data específica (slot picker)
 *   - Detectar conflitos de horário ao criar agendamento
 *   - Agenda do dia da manicure
 *
 * Sem este índice o MySQL faz table scan filtrando por manicure_id (que tem cardinalidade
 * baixa: apenas 4-10 distinct values em produção típica).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendamentos', function (Blueprint $table) {
            $table->index(
                ['manicure_id', 'data_hora_inicio', 'status'],
                'idx_agendamentos_manicure_data_status',
            );

            // Índice secundário: queries por salão + data (dashboards do dono)
            $table->index(
                ['salao_id', 'data_hora_inicio'],
                'idx_agendamentos_salao_data',
            );
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table) {
            $table->dropIndex('idx_agendamentos_manicure_data_status');
            $table->dropIndex('idx_agendamentos_salao_data');
        });
    }
};
