<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas_fiscais', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salao_id');
            $table->unsignedBigInteger('agendamento_id')->nullable();
            $table->unsignedBigInteger('comanda_id')->nullable();
            // rascunho | emitida | erro — stub local; NÃO emite na SEFAZ
            $table->string('status', 20)->default('rascunho');
            $table->string('numero', 60)->nullable();
            $table->string('chave', 60)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->foreign('salao_id')->references('id')->on('saloes')->onDelete('cascade');
            $table->foreign('agendamento_id')->references('id')->on('agendamentos')->onDelete('set null');
            $table->foreign('comanda_id')->references('id')->on('comandas')->onDelete('set null');
            $table->index(['salao_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_fiscais');
    }
};
