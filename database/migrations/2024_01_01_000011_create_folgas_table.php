<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folgas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salao_id');
            $table->date('data');
            $table->string('motivo')->nullable();
            $table->boolean('dia_todo')->default(true);
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fim')->nullable();
            $table->timestamps();

            $table->foreign('salao_id')->references('id')->on('saloes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folgas');
    }
};
