<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracoes_salao', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salao_id')->unique();
            $table->string('cor_primaria', 7)->default('#e91e8c');
            $table->boolean('permitir_agendamento_online')->default(true);
            $table->integer('intervalo_agendamento')->default(30);
            $table->integer('antecedencia_minima')->default(1);
            $table->integer('antecedencia_maxima')->default(30);
            $table->integer('cancelamento_prazo')->default(2);
            $table->decimal('taxa_cancelamento', 5, 2)->default(0);
            $table->boolean('fidelidade_ativo')->default(false);
            $table->integer('pontos_por_real')->default(1);
            $table->integer('pontos_para_desconto')->default(100);
            $table->decimal('valor_desconto_pontos', 8, 2)->default(10.00);
            $table->boolean('notificar_email')->default(true);
            $table->boolean('notificar_whatsapp')->default(false);
            $table->text('mensagem_confirmacao')->nullable();
            $table->text('mensagem_lembrete')->nullable();
            $table->integer('lembrete_horas')->default(24);
            $table->timestamps();

            $table->foreign('salao_id')->references('id')->on('saloes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracoes_salao');
    }
};
