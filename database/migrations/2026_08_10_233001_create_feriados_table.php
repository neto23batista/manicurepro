<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feriados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salao_id');
            $table->string('nome');
            $table->unsignedTinyInteger('mes'); // 1-12
            $table->unsignedTinyInteger('dia'); // 1-31
            $table->boolean('dia_todo')->default(true);
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fim')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->foreign('salao_id')->references('id')->on('saloes')->onDelete('cascade');
            $table->unique(['salao_id', 'mes', 'dia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feriados');
    }
};
