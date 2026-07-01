<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agendamento_servicos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agendamento_id');
            $table->unsignedBigInteger('servico_id');
            $table->decimal('preco', 10, 2);
            $table->integer('duracao');
            $table->timestamps();

            $table->foreign('agendamento_id')->references('id')->on('agendamentos')->onDelete('cascade');
            $table->foreign('servico_id')->references('id')->on('servicos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendamento_servicos');
    }
};
