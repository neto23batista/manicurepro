<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estoque_movimentacoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produto_id');
            $table->unsignedBigInteger('salao_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('tipo', ['entrada', 'saida', 'ajuste']);
            $table->decimal('quantidade', 10, 3);
            $table->decimal('preco_unitario', 10, 2)->nullable();
            $table->string('motivo')->nullable();
            $table->string('referencia')->nullable();
            $table->timestamps();

            $table->foreign('produto_id')->references('id')->on('produtos')->onDelete('cascade');
            $table->foreign('salao_id')->references('id')->on('saloes')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estoque_movimentacoes');
    }
};
