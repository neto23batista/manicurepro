<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salao_id');
            $table->unsignedBigInteger('categoria_id')->nullable();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->decimal('preco', 10, 2);
            $table->integer('duracao')->default(30);
            $table->decimal('comissao_percentual', 5, 2)->nullable();
            $table->boolean('combo')->default(false);
            $table->boolean('disponivel_online')->default(true);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->foreign('salao_id')->references('id')->on('saloes')->onDelete('cascade');
            $table->foreign('categoria_id')->references('id')->on('categorias_servico')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicos');
    }
};
