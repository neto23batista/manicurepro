<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacotes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salao_id');
            $table->string('nome');
            $table->unsignedInteger('sessoes');
            $table->unsignedInteger('validade_dias')->nullable();
            $table->decimal('preco', 10, 2);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->foreign('salao_id')->references('id')->on('saloes')->onDelete('cascade');
            $table->index(['salao_id', 'ativo']);
        });

        Schema::create('cliente_pacotes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('pacote_id');
            $table->unsignedInteger('sessoes_restantes');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->foreign('pacote_id')->references('id')->on('pacotes')->onDelete('cascade');
            $table->index(['cliente_id', 'sessoes_restantes', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_pacotes');
        Schema::dropIfExists('pacotes');
    }
};
