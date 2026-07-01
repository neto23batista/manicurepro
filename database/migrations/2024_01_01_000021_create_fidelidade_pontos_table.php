<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fidelidade_pontos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('salao_id');
            $table->unsignedBigInteger('agendamento_id')->nullable();
            $table->integer('pontos');
            $table->enum('tipo', ['ganho', 'resgatado', 'expirado', 'ajuste']);
            $table->string('descricao')->nullable();
            $table->timestamps();

            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->foreign('salao_id')->references('id')->on('saloes')->onDelete('cascade');
            $table->foreign('agendamento_id')->references('id')->on('agendamentos')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fidelidade_pontos');
    }
};
