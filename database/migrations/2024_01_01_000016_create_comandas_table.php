<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comandas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agendamento_id')->nullable();
            $table->unsignedBigInteger('salao_id');
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->enum('status', ['aberta', 'fechada', 'cancelada'])->default('aberta');
            $table->decimal('valor_servicos', 10, 2)->default(0);
            $table->decimal('valor_produtos', 10, 2)->default(0);
            $table->decimal('desconto', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->foreign('agendamento_id')->references('id')->on('agendamentos')->onDelete('set null');
            $table->foreign('salao_id')->references('id')->on('saloes')->onDelete('cascade');
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comandas');
    }
};
