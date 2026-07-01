<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios_funcionamento', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salao_id');
            $table->tinyInteger('dia_semana');
            $table->time('hora_abertura');
            $table->time('hora_fechamento');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['salao_id', 'dia_semana']);
            $table->foreign('salao_id')->references('id')->on('saloes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_funcionamento');
    }
};
