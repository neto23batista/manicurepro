<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificacoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('titulo');
            $table->text('mensagem');
            $table->string('tipo', 50)->default('info');
            $table->string('icone', 50)->nullable();
            $table->boolean('lida')->default(false);
            $table->json('dados')->nullable();
            $table->string('url')->nullable();
            $table->timestamp('lida_em')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacoes');
    }
};
