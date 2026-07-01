<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disponibilidades_manicure', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manicure_id');
            $table->tinyInteger('dia_semana');
            $table->time('hora_inicio');
            $table->time('hora_fim');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['manicure_id', 'dia_semana']);
            $table->foreign('manicure_id')->references('id')->on('manicures')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disponibilidades_manicure');
    }
};
