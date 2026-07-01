<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('galeria_fotos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salao_id');
            $table->unsignedBigInteger('manicure_id')->nullable();
            $table->string('caminho');
            $table->string('titulo')->nullable();
            $table->text('descricao')->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('publicar')->default(true);
            $table->boolean('destaque')->default(false);
            $table->timestamps();

            $table->foreign('salao_id')->references('id')->on('saloes')->onDelete('cascade');
            $table->foreign('manicure_id')->references('id')->on('manicures')->nullOnDelete();
            $table->index(['salao_id', 'publicar', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('galeria_fotos');
    }
};
