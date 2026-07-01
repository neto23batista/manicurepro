<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agendamentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salao_id');
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->unsignedBigInteger('manicure_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->dateTime('data_hora_inicio');
            $table->dateTime('data_hora_fim');
            $table->enum('status', ['aguardando', 'confirmado', 'em_andamento', 'concluido', 'cancelado', 'nao_compareceu'])->default('aguardando');
            $table->text('observacoes')->nullable();
            $table->text('observacoes_internas')->nullable();
            $table->enum('origem', ['web', 'app', 'balcao', 'telefone'])->default('web');
            $table->decimal('valor_total', 10, 2)->default(0);
            $table->decimal('valor_desconto', 10, 2)->default(0);
            $table->unsignedBigInteger('cupom_id')->nullable();
            $table->string('nome_cliente')->nullable();
            $table->string('telefone_cliente', 20)->nullable();
            $table->timestamps();

            $table->foreign('salao_id')->references('id')->on('saloes')->onDelete('cascade');
            $table->foreign('manicure_id')->references('id')->on('manicures')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendamentos');
    }
};
