<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_servico', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salao_id');
            $table->string('nome');
            $table->string('descricao')->nullable();
            $table->string('cor', 7)->default('#e91e8c');
            $table->string('icone', 50)->default('fa-hand-sparkles');
            $table->integer('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->foreign('salao_id')->references('id')->on('saloes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_servico');
    }
};
