<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folgas_manicure', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manicure_id');
            $table->date('data');
            $table->string('motivo')->nullable();
            $table->boolean('dia_todo')->default(true);
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fim')->nullable();
            $table->timestamps();

            $table->foreign('manicure_id')->references('id')->on('manicures')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folgas_manicure');
    }
};
