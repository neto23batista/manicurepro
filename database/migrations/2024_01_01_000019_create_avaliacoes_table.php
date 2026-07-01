<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avaliacoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agendamento_id');
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->unsignedBigInteger('manicure_id');
            $table->unsignedBigInteger('salao_id');
            $table->tinyInteger('nota');
            $table->text('comentario')->nullable();
            $table->text('resposta')->nullable();
            $table->timestamp('respondido_em')->nullable();
            $table->boolean('publicar')->default(true);
            $table->timestamps();

            $table->unique('agendamento_id');
            $table->foreign('agendamento_id')->references('id')->on('agendamentos')->onDelete('cascade');
            $table->foreign('manicure_id')->references('id')->on('manicures')->onDelete('cascade');
            $table->foreign('salao_id')->references('id')->on('saloes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avaliacoes');
    }
};
